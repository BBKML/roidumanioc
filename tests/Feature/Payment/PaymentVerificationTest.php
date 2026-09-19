<?php

namespace Tests\Feature\Payment;

use App\Actions\DeclarePayment;
use App\Livewire\Admin\Payments as AdminPayments;
use App\Mail\PaymentConfirmedMail;
use App\Mail\PaymentRejectedMail;
use App\Models\Formation;
use App\Models\Payment;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class PaymentVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $learner;

    protected Formation $formation;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Storage::fake('local');
        Mail::fake();

        $this->admin = User::factory()->admin()->create();
        $this->learner = User::factory()->create(['role' => 'apprenant', 'status' => 'actif']);
        $this->formation = Formation::create([
            'title' => 'Culture intensive', 'category' => 'Culture', 'price' => 15000, 'status' => 'publiee',
        ]);
    }

    private function declare(array $overrides = []): Payment
    {
        return app(DeclarePayment::class)->handle(
            user: $overrides['user'] ?? $this->learner,
            payable: $overrides['payable'] ?? $this->formation,
            method: $overrides['method'] ?? 'wave',
            declaredAmount: $overrides['declaredAmount'] ?? 15000,
            transactionId: $overrides['transactionId'] ?? 'TX-'.uniqid(),
            proof: $overrides['proof'] ?? UploadedFile::fake()->image('recu.jpg'),
        );
    }

    /* ---------------- Anti-fraude ---------------- */

    public function test_expected_amount_is_server_side_not_client_controlled(): void
    {
        $payment = $this->declare(['declaredAmount' => 1]); // le client prétend avoir payé 1 FCFA

        $this->assertSame(15000, $payment->amount);          // prix réel, jamais celui du client
        $this->assertSame(1, $payment->declared_amount);
        $this->assertTrue($payment->hasFlag('amount_insufficient'));
    }

    public function test_reused_transaction_id_is_flagged(): void
    {
        $this->declare(['transactionId' => 'TX-SHARED']);

        $other = User::factory()->create(['role' => 'apprenant', 'status' => 'actif']);
        $second = $this->declare([
            'user' => $other,
            'payable' => Formation::create(['title' => 'B', 'category' => 'C', 'price' => 15000, 'status' => 'publiee']),
            'transactionId' => 'TX-SHARED',
        ]);

        $this->assertTrue($second->hasFlag('duplicate_transaction'));
        $this->assertSame('high', $second->risk());
    }

    public function test_reused_proof_image_is_flagged(): void
    {
        $first = $this->declare(['transactionId' => 'TX-A']);

        // Un second paiement qui présente la même empreinte de capture.
        $other = User::factory()->create(['role' => 'apprenant', 'status' => 'actif']);
        $dupe = new Payment([
            'user_id' => $other->id, 'amount' => 15000, 'declared_amount' => 15000,
            'proof_path' => 'proofs/other.jpg', 'proof_hash' => $first->proof_hash,
        ]);
        $dupe->id = 999999; // exclu de sa propre recherche

        $this->assertContains('duplicate_proof', $dupe->runAutoCheck()['flags']);
        $this->assertSame('high', $dupe->risk());
    }

    public function test_a_learner_cannot_stack_pending_payments_for_the_same_item(): void
    {
        $this->declare();

        $this->expectException(ValidationException::class);
        $this->declare();
    }

    public function test_suspended_user_cannot_declare_a_payment(): void
    {
        $this->learner->forceFill(['status' => 'suspendu'])->save();

        $this->expectException(HttpException::class);
        $this->declare();
    }

    /* ---------------- Confirmation / accès ---------------- */

    public function test_only_confirmation_by_an_admin_unlocks_access_and_emails_the_learner(): void
    {
        $payment = $this->declare();
        $this->assertFalse($this->learner->fresh()->can('follow', $this->formation));

        Livewire::actingAs($this->admin)
            ->test(AdminPayments::class)
            ->call('confirm', $payment->id);

        $payment->refresh();
        $this->assertSame('confirme', $payment->status->value);
        $this->assertSame($this->admin->id, $payment->confirmed_by);
        $this->assertSame('validee', $payment->enrollment->fresh()->status->value);
        $this->assertTrue($this->learner->fresh()->can('follow', $this->formation));
        Mail::assertSent(PaymentConfirmedMail::class);
    }

    public function test_confirmation_is_idempotent(): void
    {
        $payment = $this->declare();

        $this->assertTrue($payment->fresh()->confirm($this->admin));
        $this->assertFalse($payment->fresh()->confirm($this->admin)); // 2e appel : no-op
    }

    /**
     * Régression (audit) : ni `confirm()` ni `reject()` ne vérifiaient que l'admin
     * n'était pas aussi le client — rien n'empêchait structurellement un compte admin,
     * s'il devenait aussi client de la plateforme, de s'auto-valider son propre paiement.
     */
    public function test_an_admin_cannot_confirm_or_reject_their_own_payment(): void
    {
        $payment = $this->declare(['user' => $this->admin]);

        $this->assertFalse($payment->fresh()->confirm($this->admin));
        $this->assertFalse($payment->fresh()->reject($this->admin, 'raison quelconque'));
        $this->assertSame('a_verifier', $payment->fresh()->status->value);

        Livewire::actingAs($this->admin)
            ->test(AdminPayments::class)
            ->call('confirm', $payment->id);

        $this->assertSame('a_verifier', $payment->fresh()->status->value);
        Mail::assertNotSent(PaymentConfirmedMail::class);
    }

    public function test_rejection_keeps_access_locked_and_records_a_reason(): void
    {
        $payment = $this->declare();

        Livewire::actingAs($this->admin)
            ->test(AdminPayments::class)
            ->call('startReject', $payment->id)
            ->set('rejectReason', 'Aucun versement retrouvé sur le compte Wave.')
            ->call('reject');

        $payment->refresh();
        $this->assertSame('refuse', $payment->status->value);
        $this->assertSame('Aucun versement retrouvé sur le compte Wave.', $payment->rejection_reason);
        $this->assertSame('refuse', $payment->enrollment->fresh()->status->value);
        $this->assertFalse($this->learner->fresh()->can('follow', $this->formation));
        Mail::assertSent(PaymentRejectedMail::class);
    }

    public function test_rejection_is_idempotent(): void
    {
        $payment = $this->declare();

        $this->assertTrue($payment->fresh()->reject($this->admin, 'Aucun versement retrouvé.'));
        $this->assertFalse($payment->fresh()->reject($this->admin, 'Nouvelle tentative.')); // 2e appel : no-op
        $this->assertSame('Aucun versement retrouvé.', $payment->fresh()->rejection_reason);
    }

    public function test_a_confirmed_payment_cannot_then_be_rejected(): void
    {
        $payment = $this->declare();
        $payment->fresh()->confirm($this->admin);

        $this->assertFalse($payment->fresh()->reject($this->admin, 'Trop tard.'));
        $this->assertSame('confirme', $payment->fresh()->status->value);
    }

    /* ---------------- Preuve (accès au fichier) ---------------- */

    public function test_proof_file_is_private_and_gated_to_admin_and_owner(): void
    {
        $payment = $this->declare();
        $stranger = User::factory()->create(['role' => 'apprenant', 'status' => 'actif']);
        $url = route('payments.proof', $payment);

        $this->get($url)->assertRedirect(route('login'));          // invité
        $this->actingAs($stranger)->get($url)->assertForbidden();  // autre apprenant
        $this->actingAs($this->learner)->get($url)->assertOk();    // propriétaire
        $this->actingAs($this->admin)->get($url)->assertOk();      // admin
    }

    /* ---------------- Commande de produit ---------------- */

    public function test_product_order_flows_through_payment_confirmation(): void
    {
        $product = ShopProduct::create([
            'name' => 'Engrais', 'category' => 'Engrais', 'price' => 12000, 'stock' => 50, 'is_active' => true,
        ]);

        $payment = $this->declare(['payable' => $product, 'declaredAmount' => 24000]);
        // action appelée avec quantity par défaut 1 -> montant attendu 12000
        $this->assertSame(12000, $payment->amount);
        $this->assertSame('excess', $payment->check_result['amount']);
        $this->assertSame('paiement', $payment->order->status->value);

        $payment->fresh()->confirm($this->admin);
        $this->assertSame('validee', $payment->order->fresh()->status->value);
    }
}

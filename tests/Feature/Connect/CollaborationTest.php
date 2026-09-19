<?php

namespace Tests\Feature\Connect;

use App\Actions\CreateConnectionRequest;
use App\Enums\CollaborationDeliveryStatus;
use App\Livewire\Collaboration\Show as CollaborationShow;
use App\Livewire\Connect\Show as ConnectShow;
use App\Models\BuyerProfile;
use App\Models\Collaboration;
use App\Models\ConnectionRequest;
use App\Models\Conversation;
use App\Models\CropOffer;
use App\Models\ProducerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CollaborationTest extends TestCase
{
    use RefreshDatabase;

    protected User $producerUser;

    protected User $buyerUser;

    protected ConnectionRequest $connectionRequest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->producerUser = User::factory()->create();
        $producerProfile = ProducerProfile::create([
            'user_id' => $this->producerUser->id, 'business_name' => 'Ferme Kouassi',
            'zone' => 'Daloa', 'activity_type' => 'recolte',
        ]);
        $offer = CropOffer::create([
            'producer_profile_id' => $producerProfile->id, 'product_name' => 'Manioc frais',
            'quantity' => 10, 'unit' => 'kg', 'location' => 'Daloa',
            'status' => 'publiee', 'is_available' => true,
        ]);

        $this->buyerUser = User::factory()->create();
        BuyerProfile::create(['user_id' => $this->buyerUser->id, 'buyer_type' => 'transformateur', 'zone' => 'Abidjan']);

        $this->connectionRequest = app(CreateConnectionRequest::class)->handle($this->buyerUser, 'acheteur', $offer);
        $this->connectionRequest->accept($this->producerUser);
        $this->connectionRequest->moveToNegotiation($this->buyerUser);
        $this->connectionRequest->propose($this->producerUser);
    }

    /** Confirme la demande via le vrai composant Livewire — exerce le chemin d'intégration réel. */
    protected function confirmAndGetCollaboration(): Collaboration
    {
        Livewire::actingAs($this->producerUser)->test(ConnectShow::class, ['connectionRequest' => $this->connectionRequest])
            ->call('confirmCollaboration')
            ->assertHasNoErrors();

        return Collaboration::where('connection_request_id', $this->connectionRequest->id)->firstOrFail();
    }

    /* ------------------------------------------------------------------ *
     |  Création
     * ------------------------------------------------------------------ */

    public function test_confirming_the_connection_request_creates_the_collaboration_and_its_delivery(): void
    {
        $collaboration = $this->confirmAndGetCollaboration();

        $this->assertSame('Manioc frais', $collaboration->agreed_product);
        $this->assertSame('kg', $collaboration->agreed_unit);
        $this->assertSame('en_cours', $collaboration->status->value);
        $this->assertNotNull($collaboration->started_at);
        $this->assertNotNull($collaboration->delivery);
        $this->assertSame('prevue', $collaboration->delivery->status->value);
    }

    /**
     * Bug remonté en usage réel : la collaboration reprenait toujours la quantité de la
     * demande d'origine et n'avait jamais de prix, même quand la négociation avait abouti
     * à des termes différents dans le fil. `createCollaborationAgreement()` doit reprendre
     * les termes de la DERNIÈRE proposition structurée, pas l'offre d'origine.
     */
    public function test_the_collaboration_reflects_the_negotiated_proposal_not_the_original_offer(): void
    {
        $conversation = Conversation::firstOrCreate(['connection_request_id' => $this->connectionRequest->id]);
        $conversation->messages()->create([
            'sender_id' => $this->producerUser->id, 'type' => 'proposition', 'body' => '',
            'proposal_terms' => ['quantity' => 8, 'unit' => 'sac', 'price_total' => 150000],
        ]);

        // Le producteur a envoyé cette proposition — c'est donc l'acheteur qui la confirme
        // (on ne peut pas accepter sa propre proposition, cf. canConfirmCollaborationBy()).
        Livewire::actingAs($this->buyerUser)->test(ConnectShow::class, ['connectionRequest' => $this->connectionRequest])
            ->call('confirmCollaboration')
            ->assertHasNoErrors();

        $collaboration = Collaboration::where('connection_request_id', $this->connectionRequest->id)->firstOrFail();

        $this->assertSame('Manioc frais', $collaboration->agreed_product);
        $this->assertSame(8.0, (float) $collaboration->agreed_quantity);
        $this->assertSame('sac', $collaboration->agreed_unit);
        $this->assertSame(150000, $collaboration->agreed_price_total);
    }

    public function test_confirming_twice_never_duplicates_the_collaboration(): void
    {
        $this->confirmAndGetCollaboration();

        Livewire::actingAs($this->producerUser)->test(ConnectShow::class, ['connectionRequest' => $this->connectionRequest])
            ->call('confirmCollaboration');

        $this->assertSame(1, Collaboration::count());
    }

    /* ------------------------------------------------------------------ *
     |  Cycle complet — chemin nominal
     * ------------------------------------------------------------------ */

    public function test_a_collaboration_travels_from_en_cours_to_terminee(): void
    {
        $collaboration = $this->confirmAndGetCollaboration();

        $this->assertTrue($collaboration->declarePayment($this->buyerUser, 150000, 'Mobile Money', 'Payé via Orange Money.'));
        $this->assertSame('paiement_declare', $collaboration->fresh()->status->value);

        $this->assertTrue($collaboration->confirmPayment($this->producerUser));
        $this->assertSame('paiement_confirme', $collaboration->fresh()->status->value);

        $this->assertTrue($collaboration->markDeliveryStep($this->producerUser, CollaborationDeliveryStatus::EnCours));
        $this->assertSame('livraison_en_cours', $collaboration->fresh()->status->value);

        $this->assertTrue($collaboration->markDeliveryStep($this->producerUser, CollaborationDeliveryStatus::Effectuee));
        $this->assertSame('livraison_en_cours', $collaboration->fresh()->status->value);
        $this->assertSame('effectuee', $collaboration->fresh()->delivery->status->value);

        $this->assertTrue($collaboration->markDeliveryStep($this->buyerUser, CollaborationDeliveryStatus::Receptionnee));
        $fresh = $collaboration->fresh();
        $this->assertSame('terminee', $fresh->status->value);
        $this->assertSame('receptionnee', $fresh->delivery->status->value);
        $this->assertNotNull($fresh->completed_at);

        $this->assertSame(1, $collaboration->payments()->count());
        $this->assertSame('confirme', $collaboration->payments()->first()->status->value);
    }

    /* ------------------------------------------------------------------ *
     |  declarePayment()
     * ------------------------------------------------------------------ */

    public function test_only_the_buyer_can_declare_a_payment(): void
    {
        $collaboration = $this->confirmAndGetCollaboration();

        $this->assertFalse($collaboration->declarePayment($this->producerUser, 100000, 'Espèces'));
        $this->assertSame('en_cours', $collaboration->fresh()->status->value);
    }

    public function test_a_third_party_cannot_declare_a_payment(): void
    {
        $collaboration = $this->confirmAndGetCollaboration();
        $intruder = User::factory()->create();

        $this->assertFalse($collaboration->declarePayment($intruder, 100000, 'Espèces'));
    }

    public function test_payment_cannot_be_declared_twice_without_a_response(): void
    {
        $collaboration = $this->confirmAndGetCollaboration();
        $collaboration->declarePayment($this->buyerUser, 100000, 'Espèces');

        $this->assertFalse($collaboration->declarePayment($this->buyerUser, 100000, 'Espèces'));
        $this->assertSame(1, $collaboration->payments()->count());
    }

    /* ------------------------------------------------------------------ *
     |  confirmPayment() / contestPayment()
     * ------------------------------------------------------------------ */

    public function test_only_the_producer_can_confirm_a_payment(): void
    {
        $collaboration = $this->confirmAndGetCollaboration();
        $collaboration->declarePayment($this->buyerUser, 100000, 'Espèces');

        $this->assertFalse($collaboration->confirmPayment($this->buyerUser));
        $this->assertSame('paiement_declare', $collaboration->fresh()->status->value);
    }

    public function test_payment_cannot_be_confirmed_before_it_is_declared(): void
    {
        $collaboration = $this->confirmAndGetCollaboration();

        $this->assertFalse($collaboration->confirmPayment($this->producerUser));
    }

    public function test_producer_contests_a_payment_and_the_buyer_can_redeclare(): void
    {
        $collaboration = $this->confirmAndGetCollaboration();
        $collaboration->declarePayment($this->buyerUser, 100000, 'Espèces');

        $this->assertTrue($collaboration->contestPayment($this->producerUser, "Je n'ai rien reçu."));

        $fresh = $collaboration->fresh();
        $this->assertSame('en_cours', $fresh->status->value); // pas litige : retour en_cours
        $this->assertSame('conteste', $fresh->payments()->latest()->first()->status->value);
        $this->assertSame("Je n'ai rien reçu.", $fresh->payments()->latest()->first()->note);

        $this->assertTrue($fresh->declarePayment($this->buyerUser, 100000, 'Mobile Money'));
        $this->assertSame(2, $fresh->payments()->count());
    }

    public function test_only_the_producer_can_contest_a_payment(): void
    {
        $collaboration = $this->confirmAndGetCollaboration();
        $collaboration->declarePayment($this->buyerUser, 100000, 'Espèces');

        $this->assertFalse($collaboration->contestPayment($this->buyerUser, 'Motif quelconque.'));
    }

    public function test_a_payment_cannot_be_contested_before_it_is_declared(): void
    {
        $collaboration = $this->confirmAndGetCollaboration();

        $this->assertFalse($collaboration->contestPayment($this->producerUser, 'Rien à contester.'));
        $this->assertSame('en_cours', $collaboration->fresh()->status->value);
    }

    /* ------------------------------------------------------------------ *
     |  markDeliveryStep()
     * ------------------------------------------------------------------ */

    public function test_delivery_cannot_start_before_payment_is_confirmed(): void
    {
        $collaboration = $this->confirmAndGetCollaboration();

        $this->assertFalse($collaboration->markDeliveryStep($this->producerUser, CollaborationDeliveryStatus::EnCours));
    }

    public function test_only_the_producer_can_mark_delivery_steps_en_cours_and_effectuee(): void
    {
        $collaboration = $this->confirmAndGetCollaboration();
        $collaboration->declarePayment($this->buyerUser, 100000, 'Espèces');
        $collaboration->confirmPayment($this->producerUser);

        $this->assertFalse($collaboration->markDeliveryStep($this->buyerUser, CollaborationDeliveryStatus::EnCours));

        $collaboration->markDeliveryStep($this->producerUser, CollaborationDeliveryStatus::EnCours);
        $this->assertFalse($collaboration->fresh()->markDeliveryStep($this->buyerUser, CollaborationDeliveryStatus::Effectuee));
    }

    public function test_only_the_buyer_can_confirm_reception(): void
    {
        $collaboration = $this->confirmAndGetCollaboration();
        $collaboration->declarePayment($this->buyerUser, 100000, 'Espèces');
        $collaboration->confirmPayment($this->producerUser);
        $collaboration->markDeliveryStep($this->producerUser, CollaborationDeliveryStatus::EnCours);
        $collaboration->markDeliveryStep($this->producerUser, CollaborationDeliveryStatus::Effectuee);

        $this->assertFalse($collaboration->fresh()->markDeliveryStep($this->producerUser, CollaborationDeliveryStatus::Receptionnee));
    }

    public function test_delivery_steps_cannot_be_skipped(): void
    {
        $collaboration = $this->confirmAndGetCollaboration();
        $collaboration->declarePayment($this->buyerUser, 100000, 'Espèces');
        $collaboration->confirmPayment($this->producerUser);

        // Depuis "prévue", on ne peut pas sauter directement à "effectuée" ou "réceptionnée".
        $this->assertFalse($collaboration->markDeliveryStep($this->producerUser, CollaborationDeliveryStatus::Effectuee));
        $this->assertFalse($collaboration->markDeliveryStep($this->buyerUser, CollaborationDeliveryStatus::Receptionnee));
    }

    /* ------------------------------------------------------------------ *
     |  cancel()
     * ------------------------------------------------------------------ */

    public function test_either_party_can_cancel_while_en_cours(): void
    {
        $collaboration = $this->confirmAndGetCollaboration();

        $this->assertTrue($collaboration->cancel($this->buyerUser));
        $this->assertSame('annulee', $collaboration->fresh()->status->value);
    }

    public function test_a_collaboration_cannot_be_cancelled_once_payment_is_declared(): void
    {
        $collaboration = $this->confirmAndGetCollaboration();
        $collaboration->declarePayment($this->buyerUser, 100000, 'Espèces');

        $this->assertFalse($collaboration->cancel($this->buyerUser));
    }

    public function test_a_third_party_cannot_cancel_a_collaboration(): void
    {
        $collaboration = $this->confirmAndGetCollaboration();
        $intruder = User::factory()->create();

        $this->assertFalse($collaboration->cancel($intruder));
        $this->assertSame('en_cours', $collaboration->fresh()->status->value);
    }

    /* ------------------------------------------------------------------ *
     |  markDisputed() — admin uniquement
     * ------------------------------------------------------------------ */

    public function test_admin_can_mark_a_collaboration_as_disputed(): void
    {
        $collaboration = $this->confirmAndGetCollaboration();
        $admin = User::factory()->admin()->create();

        $this->assertTrue($collaboration->markDisputed($admin, 'Plainte reçue par téléphone.'));
        $this->assertSame('litige', $collaboration->fresh()->status->value);
    }

    public function test_a_party_cannot_mark_their_own_collaboration_as_disputed(): void
    {
        $collaboration = $this->confirmAndGetCollaboration();

        $this->assertFalse($collaboration->markDisputed($this->producerUser, 'Motif quelconque.'));
        $this->assertSame('en_cours', $collaboration->fresh()->status->value);
    }

    public function test_marking_an_already_disputed_collaboration_is_a_no_op(): void
    {
        $collaboration = $this->confirmAndGetCollaboration();
        $admin = User::factory()->admin()->create();
        $collaboration->markDisputed($admin, 'Premier signalement.');

        $this->assertFalse($collaboration->markDisputed($admin, 'Second signalement.'));
        $this->assertSame('litige', $collaboration->fresh()->status->value);
    }

    public function test_a_terminated_collaboration_cannot_be_marked_as_disputed(): void
    {
        $collaboration = $this->confirmAndGetCollaboration();
        $collaboration->cancel($this->buyerUser);
        $admin = User::factory()->admin()->create();

        $this->assertFalse($collaboration->markDisputed($admin, 'Trop tard.'));
        $this->assertSame('annulee', $collaboration->fresh()->status->value);
    }

    /* ------------------------------------------------------------------ *
     |  Le contournement admin (Gate::before) est exclu sur les actions pair-à-pair
     * ------------------------------------------------------------------ */

    public function test_admin_can_view_but_never_declare_confirm_or_cancel_on_behalf_of_a_party(): void
    {
        $collaboration = $this->confirmAndGetCollaboration();
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)->test(CollaborationShow::class, ['collaboration' => $collaboration])
            ->assertOk()
            ->assertSet('canDeclarePayment', false)
            ->assertSet('canCancel', false)
            ->set('amountDeclared', 100000)
            ->set('method', 'Espèces')
            ->call('declarePayment')
            ->assertForbidden();

        $this->assertSame(0, $collaboration->payments()->count());
    }

    /* ------------------------------------------------------------------ *
     |  Accès à l'écran
     * ------------------------------------------------------------------ */

    public function test_both_parties_can_view_the_collaboration_screen(): void
    {
        $collaboration = $this->confirmAndGetCollaboration();

        Livewire::actingAs($this->producerUser)->test(CollaborationShow::class, ['collaboration' => $collaboration])->assertOk();
        Livewire::actingAs($this->buyerUser)->test(CollaborationShow::class, ['collaboration' => $collaboration])->assertOk();
    }

    public function test_a_third_party_cannot_view_the_collaboration_screen(): void
    {
        $collaboration = $this->confirmAndGetCollaboration();
        $intruder = User::factory()->create();

        Livewire::actingAs($intruder)->test(CollaborationShow::class, ['collaboration' => $collaboration])->assertForbidden();
    }

    public function test_the_legal_warning_is_shown_when_confirming_a_payment(): void
    {
        $collaboration = $this->confirmAndGetCollaboration();
        $collaboration->declarePayment($this->buyerUser, 100000, 'Espèces');

        Livewire::actingAs($this->producerUser)->test(CollaborationShow::class, ['collaboration' => $collaboration])
            ->assertSee('ne constitue pas une preuve bancaire automatique');
    }

    public function test_no_personal_contact_information_is_exposed_on_the_collaboration_screen(): void
    {
        $this->producerUser->update(['phone' => '0700000055', 'email' => 'secret-producer@example.com']);
        $this->buyerUser->update(['phone' => '0700000044', 'email' => 'secret-buyer@example.com']);
        $collaboration = $this->confirmAndGetCollaboration();

        Livewire::actingAs($this->buyerUser)->test(CollaborationShow::class, ['collaboration' => $collaboration])
            ->assertDontSee('0700000055')
            ->assertDontSee('secret-producer@example.com')
            ->assertDontSee('0700000044')
            ->assertDontSee('secret-buyer@example.com');
    }
}

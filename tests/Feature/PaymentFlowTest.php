<?php

namespace Tests\Feature;

use App\Enums\EnrollmentStatus;
use App\Enums\PaymentStatus;
use App\Models\Enrollment;
use App\Models\Formation;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    private function paidFormation(): Formation
    {
        return Formation::create([
            'title' => 'Formation test', 'category' => 'Culture',
            'price' => 15000, 'status' => 'publiee',
        ]);
    }

    public function test_free_formation_is_accessible_without_payment(): void
    {
        $formation = Formation::create([
            'title' => 'Gratuite', 'category' => 'Culture', 'price' => 0, 'status' => 'publiee',
        ]);
        $user = User::factory()->create(['role' => 'apprenant']);

        $this->assertTrue($user->can('follow', $formation));
    }

    public function test_paid_formation_requires_a_confirmed_payment(): void
    {
        $formation = $this->paidFormation();
        $user = User::factory()->create(['role' => 'apprenant']);

        $this->assertFalse($user->can('follow', $formation));

        $enrollment = Enrollment::create([
            'user_id' => $user->id, 'formation_id' => $formation->id,
            'status' => EnrollmentStatus::Paiement,
        ]);

        $payment = Payment::create([
            'user_id' => $user->id,
            'payable_type' => Formation::class, 'payable_id' => $formation->id,
            'enrollment_id' => $enrollment->id,
            'label' => 'Formation — '.$formation->title,
            'amount' => 15000, 'method' => 'wave',
            'declared_amount' => 15000, 'proof_path' => 'proofs/x.jpg',
        ]);

        // contrôle automatique
        $payment->runAutoCheck();
        $this->assertTrue($payment->isAmountConform());

        // toujours pas d'accès tant que non confirmé
        $this->assertFalse($user->fresh()->can('follow', $formation));

        // l'admin confirme
        $admin = User::factory()->create(['role' => 'admin']);
        $payment->confirm($admin);

        $this->assertEquals(PaymentStatus::Confirme, $payment->fresh()->status);
        $this->assertEquals(EnrollmentStatus::Validee, $enrollment->fresh()->status);
        $this->assertTrue($user->fresh()->can('follow', $formation));
    }

    public function test_insufficient_amount_is_flagged_by_auto_check(): void
    {
        $payment = new Payment([
            'amount' => 15000, 'declared_amount' => 10000, 'proof_path' => 'proofs/x.jpg',
        ]);

        $result = $payment->runAutoCheck();

        $this->assertSame('insufficient', $result['amount']);
        $this->assertSame(-5000, $result['gap']);
        $this->assertFalse($payment->isAmountConform());
    }

    public function test_admin_area_is_forbidden_to_learners(): void
    {
        $learner = User::factory()->create(['role' => 'apprenant']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($learner)->get('/admin')->assertForbidden();
        $this->actingAs($admin)->get('/admin')->assertOk();
    }

    public function test_login_redirects_by_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'password' => bcrypt('secret1234')]);
        $learner = User::factory()->create(['role' => 'apprenant', 'password' => bcrypt('secret1234')]);

        $this->post('/login', ['email' => $admin->email, 'password' => 'secret1234'])
            ->assertRedirect('/dashboard');
        $this->get('/dashboard')->assertRedirect(route('admin.dashboard'));

        $this->post('/logout');

        $this->post('/login', ['email' => $learner->email, 'password' => 'secret1234']);
        $this->get('/dashboard')->assertRedirect(route('learner.dashboard'));
    }
}

<?php

namespace Tests\Feature\Auth;

use App\Mail\NewMemberMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_registering_notifies_the_admin_by_email(): void
    {
        Mail::fake();
        $admin = User::factory()->admin()->create(['email' => 'admin@example.ci']);

        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        Mail::assertSent(NewMemberMail::class, fn ($mail) => $mail->hasTo($admin->email) && $mail->user->email === 'test@example.com');
    }

    public function test_users_without_an_email_can_register_with_a_phone_number(): void
    {
        $response = $this->post('/register', [
            'name' => 'Sans E-mail',
            'phone' => '07 00 00 00 00',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertDatabaseHas('users', ['name' => 'Sans E-mail', 'phone' => '0700000000', 'email' => null]);
    }

    public function test_registration_requires_at_least_an_email_or_a_phone_number(): void
    {
        $response = $this->post('/register', [
            'name' => 'Sans Identifiant',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors(['email', 'phone']);
    }

    public function test_choosing_producer_at_registration_redirects_to_the_producer_activation_form(): void
    {
        $response = $this->post('/register', [
            'name' => 'Nouveau Producteur',
            'email' => 'producteur@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'account_type' => ['producteur'],
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('learner.producer', absolute: false));
        // Aucun profil créé ici (champs obligatoires + mention légale non encore acceptés) —
        // seule une redirection vers le formulaire d'activation existant.
        $this->assertDatabaseCount('producer_profiles', 0);
    }

    public function test_choosing_buyer_at_registration_redirects_to_the_buyer_activation_form(): void
    {
        $response = $this->post('/register', [
            'name' => 'Nouvel Acheteur',
            'email' => 'acheteur@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'account_type' => ['acheteur'],
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('learner.buyer', absolute: false));
        $this->assertDatabaseCount('buyer_profiles', 0);
    }

    public function test_choosing_both_producer_and_buyer_prioritises_the_producer_activation_form(): void
    {
        $response = $this->post('/register', [
            'name' => 'Les Deux',
            'email' => 'les-deux@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'account_type' => ['producteur', 'acheteur'],
        ]);

        $response->assertRedirect(route('learner.producer', absolute: false));
    }

    public function test_an_invalid_account_type_is_rejected(): void
    {
        $response = $this->post('/register', [
            'name' => 'Type Invalide',
            'email' => 'invalide@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'account_type' => ['admin'],
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('account_type.0');
    }

    public function test_registration_rejects_a_phone_number_already_used(): void
    {
        User::factory()->create(['phone' => '0700000000']);

        $response = $this->post('/register', [
            'name' => 'Doublon',
            'phone' => '07 00 00 00 00',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('phone');
    }
}

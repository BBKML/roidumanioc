<?php

namespace Tests\Feature\Auth;

use App\Models\Formation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Audit UX (priorité 1) : un visiteur qui clique sur une ressource protégée (formation
 * premium ou gratuite) doit y revenir automatiquement après connexion/inscription, au
 * lieu d'atterrir sur le tableau de bord générique — via redirect()->intended(), déjà
 * utilisé par AuthenticatedSessionController et désormais aussi par
 * RegisteredUserController.
 */
class IntendedRedirectTest extends TestCase
{
    use RefreshDatabase;

    private function premiumFormation(): Formation
    {
        return Formation::create([
            'title' => 'Fertilisation avancée', 'category' => 'Culture', 'price' => 15000, 'status' => 'publiee',
        ]);
    }

    private function freeFormation(): Formation
    {
        return Formation::create([
            'title' => 'Introduction au manioc', 'category' => 'Culture', 'price' => 0, 'status' => 'publiee',
        ]);
    }

    public function test_registering_after_a_premium_formation_link_returns_to_its_checkout_page(): void
    {
        $formation = $this->premiumFormation();

        // Comme un visiteur qui clique "S'inscrire à cette formation" sur /formations/{slug}.
        $this->get(route('learner.checkout', $formation))->assertRedirect(route('login'));

        $response = $this->post('/register', [
            'name' => 'Nouvel Apprenant',
            'email' => 'nouvel.apprenant@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('learner.checkout', $formation, absolute: false));
    }

    public function test_logging_in_after_a_premium_formation_link_returns_to_its_checkout_page(): void
    {
        $formation = $this->premiumFormation();
        $user = User::factory()->create();

        $this->get(route('learner.checkout', $formation))->assertRedirect(route('login'));

        $response = $this->post('/login', [
            'login' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('learner.checkout', $formation, absolute: false));
    }

    public function test_registering_after_a_free_formation_link_returns_to_its_course_page(): void
    {
        $formation = $this->freeFormation();

        $this->get(route('learner.course', $formation))->assertRedirect(route('login'));

        $response = $this->post('/register', [
            'name' => 'Nouvel Apprenant',
            'email' => 'gratuit@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('learner.course', $formation, absolute: false));
    }

    /** Sans intention préalable (arrivée directe sur /register), le comportement historique reste inchangé. */
    public function test_registering_without_a_prior_intended_url_still_redirects_to_the_dashboard(): void
    {
        $response = $this->post('/register', [
            'name' => 'Sans Intention',
            'email' => 'sans.intention@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
    }
}

<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('services.google.client_id', 'test-client-id');
        config()->set('services.google.client_secret', 'test-secret');
        config()->set('services.google.redirect', '/auth/google/callback');
    }

    private function fakeGoogle(array $profile): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'fake-access-token']),
            'openidconnect.googleapis.com/v1/userinfo' => Http::response($profile),
        ]);
    }

    private function callbackWithState(array $query = []): TestResponse
    {
        // Pose le state en session comme le ferait la redirection.
        $this->withSession(['google_oauth_state' => 'STATE123']);

        return $this->get(route('google.callback', array_merge(['state' => 'STATE123', 'code' => 'auth-code'], $query)));
    }

    public function test_disabled_when_no_credentials(): void
    {
        config()->set('services.google.client_id', null);

        $this->get(route('google.redirect'))->assertNotFound();
        $this->get(route('google.callback'))->assertNotFound();
    }

    public function test_redirect_sets_state_and_points_to_google(): void
    {
        $res = $this->get(route('google.redirect'));

        $res->assertRedirect();
        $this->assertStringContainsString('accounts.google.com', $res->headers->get('Location'));
        $this->assertNotEmpty(session('google_oauth_state'));
    }

    public function test_state_mismatch_is_rejected(): void
    {
        $this->withSession(['google_oauth_state' => 'GOOD']);

        $this->get(route('google.callback', ['state' => 'EVIL', 'code' => 'x']))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');
    }

    public function test_unverified_google_email_is_refused(): void
    {
        $this->fakeGoogle(['sub' => '123', 'email' => 'x@gmail.com', 'email_verified' => false, 'name' => 'X']);

        $this->callbackWithState()
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_new_google_user_is_created_as_active_learner(): void
    {
        $this->fakeGoogle(['sub' => 'g-999', 'email' => 'fatou@gmail.com', 'email_verified' => true, 'name' => 'Fatou K.']);

        $this->callbackWithState()->assertRedirect(route('dashboard'));

        $user = User::where('email', 'fatou@gmail.com')->firstOrFail();
        $this->assertSame('apprenant', $user->role->value);
        $this->assertSame('actif', $user->status->value);
        $this->assertSame('google', $user->provider);
        $this->assertTrue($user->isOAuthOnly());
        $this->assertAuthenticatedAs($user);
    }

    public function test_google_links_to_an_existing_account_by_verified_email(): void
    {
        $existing = User::factory()->create(['email' => 'kouassi@example.ci', 'role' => 'apprenant']);

        $this->fakeGoogle(['sub' => 'g-1', 'email' => 'kouassi@example.ci', 'email_verified' => true, 'name' => 'Kouassi']);

        $this->callbackWithState();

        $existing->refresh();
        $this->assertSame('google', $existing->provider);
        $this->assertSame('g-1', $existing->provider_id);
        $this->assertAuthenticatedAs($existing);
    }

    public function test_google_cannot_log_into_a_suspended_account(): void
    {
        User::factory()->suspended()->create(['email' => 'yao@example.ci']);

        $this->fakeGoogle(['sub' => 'g-2', 'email' => 'yao@example.ci', 'email_verified' => true, 'name' => 'Yao']);

        $this->callbackWithState()
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}

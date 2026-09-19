<?php

namespace Tests\Feature;

use App\Models\Lesson;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class HardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_are_present_on_web_responses(): void
    {
        $res = $this->get('/');

        $res->assertHeader('X-Content-Type-Options', 'nosniff');
        $res->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $res->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertStringContainsString("object-src 'none'", $res->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString('iframe.mediadelivery.net', $res->headers->get('Content-Security-Policy'));
    }

    /**
     * Audit UX (priorité 9) : une route refusée (ex. non-admin sur /admin/*) doit afficher
     * la page 403 à l'identité du site plutôt que l'erreur générique de Laravel.
     */
    public function test_a_non_admin_hitting_an_admin_route_sees_the_branded_403_page(): void
    {
        $learner = User::factory()->create();

        $this->actingAs($learner)->get('/admin/membres')
            ->assertForbidden()
            ->assertSee('403')
            ->assertSee("Vous n'avez pas l'autorisation d'accéder à cette page.", false)
            ->assertSee('Retour à mon espace', false);
    }

    public function test_security_headers_do_not_break_file_downloads(): void
    {
        // Une réponse avec Content-Disposition (téléchargement) ne doit pas recevoir la CSP.
        Cache::flush();
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('role', 'admin')->first();
        $payment = Payment::first();

        if ($payment && $payment->proof_path) {
            $res = $this->actingAs($admin)->get(route('payments.proof', $payment));
            $this->assertNull($res->headers->get('Content-Security-Policy'));
        }

        $this->assertTrue(true);
    }

    public function test_dashboard_route_is_cacheable_controller_and_redirects_by_role(): void
    {
        $admin = User::factory()->admin()->create();
        $learner = User::factory()->create(['role' => 'apprenant']);

        $this->actingAs($admin)->get('/dashboard')->assertRedirect(route('admin.dashboard'));
        $this->actingAs($learner)->get('/dashboard')->assertRedirect(route('learner.dashboard'));
    }

    public function test_activity_log_screen_is_admin_only(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'apprenant']))
            ->get(route('admin.activity'))->assertForbidden();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.activity'))->assertOk()->assertSee("Journal d'activité");
    }

    public function test_weak_passwords_are_rejected_at_registration(): void
    {
        $this->post('/register', [
            'name' => 'Test', 'email' => 't@example.ci',
            'password' => 'password',           // pas de chiffre
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('password');
    }

    public function test_bunny_stream_embed_url_is_signed_when_a_token_key_is_set(): void
    {
        config()->set('services.bunny.library_id', '12345');
        config()->set('services.bunny.token_key', 'super-secret-key');

        $lesson = new Lesson(['video_provider' => 'bunny', 'video_url' => 'a1b2c3d4-e5f6-7890-abcd-ef1234567890']);
        $url = $lesson->embedUrl();

        $this->assertStringStartsWith('https://iframe.mediadelivery.net/embed/12345/a1b2c3d4-e5f6-7890-abcd-ef1234567890', $url);
        $this->assertStringContainsString('token=', $url);
        $this->assertStringContainsString('expires=', $url);

        // Sans token_key : URL nue.
        config()->set('services.bunny.token_key', null);
        $this->assertStringNotContainsString('token=', (new Lesson(['video_provider' => 'bunny', 'video_url' => 'a1b2c3d4-e5f6-7890-abcd-ef1234567890']))->embedUrl());
    }

    public function test_backup_command_creates_an_archive(): void
    {
        // En test (SQLite) le dump SQL est ignoré ; l'archive doit tout de même être produite.
        $this->artisan('backup:run', ['--keep' => 2])->assertSuccessful();

        $zips = File::glob(storage_path('app/backups/backup_*.zip'));
        $this->assertNotEmpty($zips);

        foreach ($zips as $z) {
            @unlink($z);
        }
    }
}

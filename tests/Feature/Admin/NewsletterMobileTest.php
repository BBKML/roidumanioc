<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Newsletter;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Audit UX (Phase 3) : ce tableau (5 colonnes) s'adapte désormais en cartes sous 700px,
 * même mécanisme que Members/Orders/MarketplaceModeration.
 */
class NewsletterMobileTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_table_is_mobile_ready(): void
    {
        $admin = User::factory()->admin()->create();
        NewsletterSubscriber::create(['email' => 'fatou@example.ci', 'source' => 'footer']);

        Livewire::actingAs($admin)->test(Newsletter::class)
            ->set('filter', 'tous')
            ->assertSee('stack-mobile', false)
            ->assertSee('data-label="Source"', false);
    }

    public function test_screen_is_admin_only(): void
    {
        $learner = User::factory()->create();

        $this->actingAs($learner)->get(route('admin.newsletter'))->assertForbidden();
    }
}

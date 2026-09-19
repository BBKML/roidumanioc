<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Audit UX (Phase 3) : ce tableau (5 colonnes, aucune action) s'adapte désormais en
 * cartes sous 700px, même mécanisme que Members/Orders/MarketplaceModeration.
 */
class ActivityLogMobileTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_table_is_mobile_ready(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)->test(ActivityLog::class)
            ->assertOk()
            ->assertSee('stack-mobile', false);
    }

    public function test_screen_is_admin_only(): void
    {
        $learner = User::factory()->create();

        $this->actingAs($learner)->get(route('admin.activity'))->assertForbidden();
    }
}

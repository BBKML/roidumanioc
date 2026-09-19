<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Formations;
use App\Models\Formation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Audit architecture : dernier tableau de gestion cité par l'utilisateur à ne pas encore
 * s'adapter en cartes sous 700px (Members/Orders/MarketplaceModeration + 8 autres l'ont
 * déjà) — même mécanisme stack-mobile réutilisé, rien de nouveau.
 */
class FormationsMobileTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_table_is_mobile_ready(): void
    {
        $admin = User::factory()->admin()->create();
        Formation::create(['title' => 'Culture du manioc', 'category' => 'Culture', 'price' => 0, 'status' => 'publiee']);

        Livewire::actingAs($admin)->test(Formations::class)
            ->assertSee('stack-mobile', false)
            ->assertSee('data-label="Catégorie"', false);
    }

    public function test_screen_is_admin_only(): void
    {
        $learner = User::factory()->create();

        $this->actingAs($learner)->get(route('admin.formations'))->assertForbidden();
    }
}

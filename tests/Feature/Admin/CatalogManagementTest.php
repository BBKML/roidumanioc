<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Formations as FormationsComponent;
use App\Livewire\Admin\LessonManager;
use App\Livewire\Admin\MarketplaceModeration;
use App\Models\Formation;
use App\Models\MarketplaceListing;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class CatalogManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->seed(DatabaseSeeder::class);
        $this->actingAs(User::where('role', 'admin')->firstOrFail());
    }

    public function test_admin_creates_a_paid_formation_which_becomes_premium(): void
    {
        Livewire::test(FormationsComponent::class)
            ->call('new')
            ->set('title', 'Irrigation du manioc')
            ->set('category', 'Culture')
            ->set('price', 9000)
            ->set('status', 'publiee')
            ->call('save')
            ->assertHasNoErrors();

        $formation = Formation::where('title', 'Irrigation du manioc')->firstOrFail();
        $this->assertSame('premium', $formation->access->value);
        $this->assertNotEmpty($formation->slug);
        $this->assertTrue($formation->status->value === 'publiee');
    }

    public function test_admin_adds_a_lesson_and_reorders_it(): void
    {
        $formation = Formation::has('lessons', '>=', 2)->firstOrFail();
        [$first, $second] = $formation->lessons()->orderBy('position')->take(2)->get()->all();

        Livewire::test(LessonManager::class, ['formation' => $formation])
            ->call('new')
            ->set('title', 'Nouvelle leçon test')
            ->set('type', 'video')
            ->call('save')
            ->assertHasNoErrors()
            ->call('move', $first->id, 'down');

        $this->assertDatabaseHas('lessons', ['title' => 'Nouvelle leçon test', 'formation_id' => $formation->id]);
        // Après un "move down", l'ancienne 1re leçon passe derrière l'ancienne 2e.
        $this->assertGreaterThan($second->fresh()->position, $first->fresh()->position);
    }

    public function test_admin_approves_a_pending_marketplace_listing(): void
    {
        $pending = MarketplaceListing::create([
            'title' => 'Annonce en attente test',
            'type' => 'Récolte',
            'location' => 'Test',
            'price_label' => '100 FCFA',
            'seller_name' => 'Producteur Test',
            'status' => 'en_attente',
        ]);

        Livewire::test(MarketplaceModeration::class)
            ->call('approve', $pending->id);

        $this->assertSame('validee', $pending->fresh()->status->value);
    }

    /**
     * Audit UX (priorité 7) : publier/refuser/retirer une annonce a un effet immédiat sur
     * la vitrine publique — ces actions demandent désormais une confirmation, comme la
     * suppression le fait déjà.
     */
    /** Audit architecture : état vide contextualisé par onglet plutôt qu'un texte générique. */
    public function test_the_empty_state_is_contextualised_by_tab(): void
    {
        Livewire::test(MarketplaceModeration::class)
            ->set('filter', 'refuse')
            ->assertSee('Aucune annonce refusée.');
    }

    public function test_publish_reject_and_unpublish_require_confirmation(): void
    {
        $pending = MarketplaceListing::create([
            'title' => 'Annonce en attente', 'type' => 'Récolte', 'location' => 'Test',
            'price_label' => '100 FCFA', 'seller_name' => 'Producteur Test', 'status' => 'en_attente',
        ]);
        $published = MarketplaceListing::create([
            'title' => 'Annonce publiée', 'type' => 'Récolte', 'location' => 'Test',
            'price_label' => '100 FCFA', 'seller_name' => 'Producteur Test', 'status' => 'validee',
        ]);

        Livewire::test(MarketplaceModeration::class)
            ->set('filter', 'tous')
            ->assertSee('data-confirm="Publier cette annonce sur la vitrine publique ?"', false)
            ->assertSee('data-confirm="Refuser cette annonce ?"', false)
            ->assertSee('data-confirm="Retirer cette annonce de la vitrine publique ?"', false);
    }

    /**
     * Audit UX (Phase 2) : ce tableau (7 colonnes) n'avait ni pagination ni adaptation
     * mobile — mêmes principes que Members/Orders désormais (gabarit de pagination
     * existant + classe CSS opt-in stack-mobile).
     */
    public function test_the_marketplace_listing_is_paginated_and_mobile_ready(): void
    {
        MarketplaceListing::insert(array_map(fn ($i) => [
            'title' => "Annonce $i", 'type' => 'Récolte', 'location' => 'Test',
            'price_label' => '100 FCFA', 'seller_name' => 'Producteur Test', 'status' => 'validee',
            'created_at' => now(), 'updated_at' => now(),
        ], range(1, 25)));

        Livewire::test(MarketplaceModeration::class)
            ->set('filter', 'tous')
            ->assertSee('stack-mobile', false)
            ->assertViewHas('listings', fn ($listings) => $listings->perPage() === 20 && $listings->hasPages());
    }
}

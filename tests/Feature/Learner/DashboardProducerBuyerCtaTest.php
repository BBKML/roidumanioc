<?php

namespace Tests\Feature\Learner;

use App\Livewire\Learner\Dashboard;
use App\Models\BuyerProfile;
use App\Models\ProducerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Audit UX (Phase 3) : le bloc discret d'activation producteur/acheteur du tableau de
 * bord apprenant (introduit en Phase 2) doit utiliser la formulation unifiée
 * "Devenir producteur"/"Devenir acheteur" avant activation, s'adapter à ce qui est déjà
 * activé, et disparaître une fois les deux profils créés.
 */
class DashboardProducerBuyerCtaTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_cta_offers_both_when_neither_profile_is_activated(): void
    {
        $user = User::factory()->create(['role' => 'apprenant', 'status' => 'actif']);

        Livewire::actingAs($user)->test(Dashboard::class)
            ->assertSee('Vous produisez ou recherchez du manioc ?')
            ->assertSee('Devenir producteur')
            ->assertSee('Devenir acheteur');
    }

    public function test_the_cta_only_offers_buyer_once_producer_is_activated(): void
    {
        $user = User::factory()->create(['role' => 'apprenant', 'status' => 'actif']);
        ProducerProfile::create([
            'user_id' => $user->id, 'business_name' => 'Ferme Kouassi', 'zone' => 'Daloa', 'activity_type' => 'recolte',
        ]);

        Livewire::actingAs($user)->test(Dashboard::class)
            ->assertSee('Vous achetez aussi du manioc ?')
            ->assertSee('Devenir acheteur')
            ->assertDontSee('Devenir producteur');
    }

    public function test_the_cta_disappears_once_both_profiles_are_activated(): void
    {
        $user = User::factory()->create(['role' => 'apprenant', 'status' => 'actif']);
        ProducerProfile::create([
            'user_id' => $user->id, 'business_name' => 'Ferme Kouassi', 'zone' => 'Daloa', 'activity_type' => 'recolte',
        ]);
        BuyerProfile::create([
            'user_id' => $user->id, 'buyer_type' => 'transformateur', 'zone' => 'Abidjan',
        ]);

        Livewire::actingAs($user)->test(Dashboard::class)
            ->assertDontSee('Devenir producteur')
            ->assertDontSee('Devenir acheteur');
    }
}

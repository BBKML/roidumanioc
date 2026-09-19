<?php

namespace Tests\Feature\Admin;

use App\Actions\CreateConnectionRequest;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Connect\Show as ConnectShow;
use App\Models\BuyerNeed;
use App\Models\BuyerProfile;
use App\Models\Collaboration;
use App\Models\ConnectionRequest;
use App\Models\CropOffer;
use App\Models\ProducerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tableau de bord UNIQUE de l'administration (Phase 16 — même principe que Phase 15 côté
 * apprenant). Remplace `Admin\ConnectStatsTest` (composant/écran séparé supprimé) : les
 * agrégats "mise en relation" sont désormais une section de plus sur /admin, testée ici.
 */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_sees_platform_wide_connect_counters_on_the_main_dashboard(): void
    {
        $producerUser = User::factory()->create();
        $producerProfile = ProducerProfile::create([
            'user_id' => $producerUser->id, 'business_name' => 'Ferme Kouassi', 'zone' => 'Daloa', 'activity_type' => 'recolte',
        ]);
        $offer = CropOffer::create([
            'producer_profile_id' => $producerProfile->id, 'product_name' => 'Manioc frais',
            'quantity' => 10, 'unit' => 'kg', 'location' => 'Daloa',
            'status' => 'publiee', 'is_available' => true, 'price_indicative' => 75000,
        ]);

        $buyerUser = User::factory()->create();
        $buyerProfile = BuyerProfile::create(['user_id' => $buyerUser->id, 'buyer_type' => 'transformateur', 'zone' => 'Abidjan']);
        BuyerNeed::create([
            'buyer_profile_id' => $buyerProfile->id, 'product_wanted' => 'Igname',
            'quantity' => 20, 'unit' => 'kg', 'location' => 'Abidjan', 'frequency' => 'ponctuel', 'status' => 'ouvert',
        ]);

        $connectionRequest = app(CreateConnectionRequest::class)->handle($buyerUser, 'acheteur', $offer);
        $connectionRequest->accept($producerUser);
        $connectionRequest->moveToNegotiation($buyerUser);
        $connectionRequest->propose($producerUser);

        Livewire::actingAs($producerUser)->test(ConnectShow::class, ['connectionRequest' => $connectionRequest])
            ->call('confirmCollaboration');

        Auth::logout();

        Livewire::actingAs($this->admin)->test(Dashboard::class)
            ->assertViewHas('producersTotal', 1)
            ->assertViewHas('buyersTotal', 1)
            ->assertViewHas('offersTotal', 1)
            ->assertViewHas('needsTotal', 1)
            ->assertViewHas('collaborationsTotal', 1)
            ->assertViewHas('estimatedVolume', 75000)
            ->assertSee('Mise en relation');
    }

    public function test_cancelled_collaborations_are_excluded_from_the_estimated_volume(): void
    {
        $producerUser = User::factory()->create();
        $producerProfile = ProducerProfile::create([
            'user_id' => $producerUser->id, 'business_name' => 'Ferme Kouassi', 'zone' => 'Daloa', 'activity_type' => 'recolte',
        ]);
        $buyerUser = User::factory()->create();
        $buyerProfile = BuyerProfile::create(['user_id' => $buyerUser->id, 'buyer_type' => 'transformateur', 'zone' => 'Abidjan']);

        $connectionRequest = ConnectionRequest::create([
            'requester_user_id' => $buyerUser->id, 'requester_role' => 'acheteur',
            'producer_profile_id' => $producerProfile->id, 'buyer_profile_id' => $buyerProfile->id,
            'crop_offer_id' => CropOffer::create([
                'producer_profile_id' => $producerProfile->id, 'product_name' => 'Manioc frais',
                'quantity' => 10, 'unit' => 'kg', 'location' => 'Daloa',
                'status' => 'publiee', 'is_available' => true, 'price_indicative' => 100000,
            ])->id,
        ]);

        Collaboration::create([
            'connection_request_id' => $connectionRequest->id,
            'producer_profile_id' => $producerProfile->id, 'buyer_profile_id' => $buyerProfile->id,
            'agreed_product' => 'Manioc frais', 'agreed_quantity' => 10, 'agreed_unit' => 'kg',
            'status' => 'annulee',
        ]);

        Livewire::actingAs($this->admin)->test(Dashboard::class)
            ->assertViewHas('estimatedVolume', 0)
            ->assertViewHas('collaborationsTotal', 1);
    }

    public function test_a_learner_cannot_access_the_dashboard(): void
    {
        $learner = User::factory()->create();

        $this->actingAs($learner)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    /** Le cœur du correctif : plus de second écran "vue d'ensemble" séparé. */
    public function test_the_standalone_connect_stats_route_no_longer_exists(): void
    {
        $this->assertFalse(Route::has('admin.connect-stats'));
    }

    public function test_the_nav_no_longer_has_a_second_overview_link(): void
    {
        $html = $this->actingAs($this->admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('Chiffres de la mise en relation', $html);
        $this->assertSame(1, substr_count($html, 'class="nav-item on"'));
    }
}

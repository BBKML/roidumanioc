<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Producers;
use App\Livewire\Admin\ProducerShow;
use App\Models\BuyerProfile;
use App\Models\Collaboration;
use App\Models\ConnectionRequest;
use App\Models\CropOffer;
use App\Models\ProducerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ConnectProducersTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    protected function makeProducer(string $name): ProducerProfile
    {
        $user = User::factory()->create();

        return ProducerProfile::create([
            'user_id' => $user->id, 'business_name' => $name, 'zone' => 'Daloa', 'activity_type' => 'recolte',
        ]);
    }

    public function test_admin_sees_unverified_producers_in_the_default_tab(): void
    {
        $this->makeProducer('Ferme non vérifiée');
        $verified = $this->makeProducer('Ferme déjà vérifiée');
        $verified->forceFill(['verified_at' => now(), 'verified_by' => $this->admin->id])->save();

        Livewire::actingAs($this->admin)->test(Producers::class)
            ->assertSee('Ferme non vérifiée')
            ->assertDontSee('Ferme déjà vérifiée');
    }

    /**
     * Audit UX (Phase 3) : ce tableau (6 colonnes) s'adapte désormais en cartes sous
     * 700px, même mécanisme que Members/Orders/MarketplaceModeration.
     */
    public function test_the_table_is_mobile_ready(): void
    {
        $this->makeProducer('Ferme Kouassi');

        Livewire::actingAs($this->admin)->test(Producers::class)
            ->assertSee('stack-mobile', false)
            ->assertSee('data-label="Zone"', false);
    }

    /**
     * Audit architecture : tri par colonne ajouté sur ce tableau (aucun tableau du
     * back-office n'en avait). Clic 1 = ascendant, clic 2 sur la même colonne = descendant.
     */
    public function test_clicking_a_sortable_column_toggles_order(): void
    {
        $this->makeProducer('Zoé Ferme');
        $this->makeProducer('Amadou Ferme');

        Livewire::actingAs($this->admin)->test(Producers::class)
            ->set('filter', 'tous')
            ->call('sortBy', 'business_name')
            ->assertSet('sort', 'business_name')
            ->assertSet('direction', 'asc')
            ->assertViewHas('profiles', fn ($profiles) => $profiles->pluck('business_name')->first() === 'Amadou Ferme')
            ->call('sortBy', 'business_name')
            ->assertSet('direction', 'desc')
            ->assertViewHas('profiles', fn ($profiles) => $profiles->pluck('business_name')->first() === 'Zoé Ferme');
    }

    /** Sécurité : $sort vient de l'URL — une colonne non autorisée doit être ignorée. */
    public function test_sorting_by_an_unknown_column_is_ignored(): void
    {
        Livewire::actingAs($this->admin)->test(Producers::class)
            ->call('sortBy', 'verified_by')
            ->assertSet('sort', 'created_at')
            ->assertSuccessful();
    }

    public function test_search_filters_by_business_name_or_zone(): void
    {
        $this->makeProducer('Ferme Kouassi');
        $this->makeProducer('Coopérative Daloa');

        Livewire::actingAs($this->admin)->test(Producers::class)
            ->set('filter', 'tous')
            ->set('search', 'Kouassi')
            ->assertSee('Ferme Kouassi')
            ->assertDontSee('Coopérative Daloa');
    }

    public function test_admin_verifies_a_producer_from_the_list(): void
    {
        $profile = $this->makeProducer('Ferme Kouassi');

        Livewire::actingAs($this->admin)->test(Producers::class)
            ->call('verify', $profile);

        $fresh = $profile->fresh();
        $this->assertNotNull($fresh->verified_at);
        $this->assertSame($this->admin->id, $fresh->verified_by);
    }

    public function test_verifying_an_already_verified_producer_is_a_no_op(): void
    {
        $profile = $this->makeProducer('Ferme Kouassi');
        $profile->forceFill(['verified_at' => now()->subDay(), 'verified_by' => $this->admin->id])->save();
        $originalVerifiedAt = $profile->verified_at;

        Livewire::actingAs($this->admin)->test(Producers::class)
            ->call('verify', $profile);

        $this->assertEquals($originalVerifiedAt, $profile->fresh()->verified_at);
    }

    public function test_admin_revokes_a_verification_from_the_list(): void
    {
        $profile = $this->makeProducer('Ferme Kouassi');
        $profile->forceFill(['verified_at' => now(), 'verified_by' => $this->admin->id])->save();

        Livewire::actingAs($this->admin)->test(Producers::class)
            ->call('reject', $profile);

        $fresh = $profile->fresh();
        $this->assertNull($fresh->verified_at);
        $this->assertNull($fresh->verified_by);
    }

    public function test_revoking_an_unverified_producer_is_a_no_op(): void
    {
        $profile = $this->makeProducer('Ferme Kouassi');

        $this->assertFalse($profile->rejectVerification());
        $this->assertFalse($profile->fresh()->isVerified());
    }

    public function test_verification_changes_are_logged(): void
    {
        $profile = $this->makeProducer('Ferme Kouassi');

        Livewire::actingAs($this->admin)->test(Producers::class)
            ->call('verify', $profile);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => ProducerProfile::class,
            'subject_id' => $profile->id,
            'causer_id' => $this->admin->id,
            'log_name' => 'producer_profile',
        ]);
    }

    public function test_a_learner_cannot_access_the_producers_screen(): void
    {
        $learner = User::factory()->create();

        $this->actingAs($learner)->get(route('admin.producers'))->assertForbidden();
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.producers'))->assertRedirect(route('login'));
    }

    public function test_verified_badge_appears_on_the_public_catalog(): void
    {
        $profile = $this->makeProducer('Ferme Kouassi');
        CropOffer::create([
            'producer_profile_id' => $profile->id, 'product_name' => 'Manioc frais',
            'quantity' => 10, 'unit' => 'kg', 'location' => 'Daloa',
            'status' => 'publiee', 'is_available' => true,
        ]);

        $this->get(route('producers.index'))->assertOk()->assertDontSee('Vérifié');

        $profile->verify($this->admin);

        $this->get(route('producers.index'))->assertOk()->assertSee('Vérifié');
    }

    /* ------------------------------------------------------------------ *
     |  Fiche détail (nouveau, Phase 10)
     * ------------------------------------------------------------------ */

    public function test_the_detail_page_shows_profile_offers_and_actions(): void
    {
        $profile = $this->makeProducer('Ferme Kouassi');
        CropOffer::create([
            'producer_profile_id' => $profile->id, 'product_name' => 'Manioc frais',
            'quantity' => 10, 'unit' => 'kg', 'location' => 'Daloa',
            'status' => 'publiee', 'is_available' => true,
        ]);

        Livewire::actingAs($this->admin)->test(ProducerShow::class, ['producerProfile' => $profile])
            ->assertSee('Ferme Kouassi')
            ->assertSee('Manioc frais')
            ->assertSee('Vérifier');
    }

    /**
     * Audit UX (Phase 2) : ces deux blocs étaient limités à 10 lignes sans échappatoire —
     * paginés désormais (gabarit de pagination existant, `pageName` distinct par bloc).
     */
    public function test_the_detail_page_paginates_connection_requests(): void
    {
        $producer = $this->makeProducer('Ferme Kouassi');
        $buyerUser = User::factory()->create();
        $buyerProfile = BuyerProfile::create([
            'user_id' => $buyerUser->id, 'buyer_type' => 'transformateur', 'zone' => 'Abidjan',
        ]);
        $offer = CropOffer::create([
            'producer_profile_id' => $producer->id, 'product_name' => 'Manioc frais',
            'quantity' => 10, 'unit' => 'kg', 'location' => 'Daloa',
            'status' => 'publiee', 'is_available' => true,
        ]);

        foreach (range(1, 11) as $i) {
            ConnectionRequest::create([
                'requester_user_id' => $buyerUser->id, 'requester_role' => 'acheteur',
                'crop_offer_id' => $offer->id,
                'producer_profile_id' => $producer->id, 'buyer_profile_id' => $buyerProfile->id,
            ]);
        }

        Livewire::actingAs($this->admin)->test(ProducerShow::class, ['producerProfile' => $producer])
            ->assertSee('Demandes (11)')
            ->assertViewHas('connectionRequests', fn ($requests) => $requests->perPage() === 10 && $requests->hasPages());

        // Même principe pour les collaborations (code identique, `pageName` différent) —
        // vérifié ici avec le compteur affiché dans le titre du bloc.
        $cr = ConnectionRequest::create([
            'requester_user_id' => $buyerUser->id, 'requester_role' => 'acheteur',
            'crop_offer_id' => $offer->id,
            'producer_profile_id' => $producer->id, 'buyer_profile_id' => $buyerProfile->id,
        ]);
        Collaboration::create([
            'connection_request_id' => $cr->id, 'producer_profile_id' => $producer->id,
            'buyer_profile_id' => $buyerProfile->id, 'agreed_product' => 'Manioc frais',
            'agreed_quantity' => 10, 'agreed_unit' => 'kg', 'status' => 'en_cours',
        ]);

        Livewire::actingAs($this->admin)->test(ProducerShow::class, ['producerProfile' => $producer])
            ->assertSee('Collaborations (1)')
            ->assertViewHas('collaborations', fn ($collabs) => $collabs->perPage() === 10);
    }

    public function test_admin_verifies_a_producer_from_the_detail_page(): void
    {
        $profile = $this->makeProducer('Ferme Kouassi');

        Livewire::actingAs($this->admin)->test(ProducerShow::class, ['producerProfile' => $profile])
            ->call('verify');

        $this->assertNotNull($profile->fresh()->verified_at);
    }

    public function test_admin_revokes_a_verification_from_the_detail_page(): void
    {
        $profile = $this->makeProducer('Ferme Kouassi');
        $profile->forceFill(['verified_at' => now(), 'verified_by' => $this->admin->id])->save();

        Livewire::actingAs($this->admin)->test(ProducerShow::class, ['producerProfile' => $profile])
            ->call('reject');

        $this->assertNull($profile->fresh()->verified_at);
    }

    public function test_a_learner_cannot_access_the_detail_page(): void
    {
        $profile = $this->makeProducer('Ferme Kouassi');
        $learner = User::factory()->create();

        $this->actingAs($learner)->get(route('admin.producers.show', $profile))->assertForbidden();
    }
}

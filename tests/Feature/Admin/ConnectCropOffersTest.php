<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\CropOffers;
use App\Models\CropOffer;
use App\Models\ProducerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ConnectCropOffersTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected ProducerProfile $producerProfile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();

        $producer = User::factory()->create();
        $this->producerProfile = ProducerProfile::create([
            'user_id' => $producer->id, 'business_name' => 'Ferme Kouassi', 'zone' => 'Daloa', 'activity_type' => 'recolte',
        ]);
    }

    protected function makeOffer(string $product, string $status = 'publiee'): CropOffer
    {
        return CropOffer::create([
            'producer_profile_id' => $this->producerProfile->id, 'product_name' => $product,
            'quantity' => 10, 'unit' => 'kg', 'location' => 'Daloa',
            'status' => $status, 'is_available' => true,
        ]);
    }

    public function test_admin_sees_all_offers(): void
    {
        $this->makeOffer('Manioc frais');

        Livewire::actingAs($this->admin)->test(CropOffers::class)
            ->assertSee('Manioc frais')
            ->assertSee('Ferme Kouassi');
    }

    /** Audit UX (Phase 3) : tableau adapté en cartes sous 700px (même mécanisme). */
    public function test_the_table_is_mobile_ready(): void
    {
        $this->makeOffer('Manioc frais');

        Livewire::actingAs($this->admin)->test(CropOffers::class)
            ->assertSee('stack-mobile', false)
            ->assertSee('data-label="Producteur"', false);
    }

    public function test_search_filters_by_product_or_producer(): void
    {
        $this->makeOffer('Manioc frais');
        $this->makeOffer('Igname');

        Livewire::actingAs($this->admin)->test(CropOffers::class)
            ->set('search', 'Igname')
            ->assertSee('Igname')
            ->assertDontSee('Manioc frais');
    }

    /** Audit architecture : état vide contextualisé + bouton de réinitialisation. */
    public function test_a_search_with_no_results_offers_to_reset_it(): void
    {
        $this->makeOffer('Manioc frais');

        Livewire::actingAs($this->admin)->test(CropOffers::class)
            ->set('search', 'Personne Ne Vend Ça')
            ->assertSee('Aucune offre ne correspond à cette recherche.')
            ->call('resetFilters')
            ->assertSet('search', '')
            ->assertSet('filter', 'tous');
    }

    public function test_filter_tabs_scope_by_status(): void
    {
        $this->makeOffer('Manioc frais', 'publiee');
        $this->makeOffer('Manioc archivé', 'archivee');

        Livewire::actingAs($this->admin)->test(CropOffers::class)
            ->set('filter', 'archivee')
            ->assertSee('Manioc archivé')
            ->assertDontSee('Manioc frais');
    }

    public function test_admin_archives_an_offer(): void
    {
        $offer = $this->makeOffer('Manioc frais');

        Livewire::actingAs($this->admin)->test(CropOffers::class)
            ->call('archive', $offer);

        $this->assertSame('archivee', $offer->fresh()->status->value);
    }

    public function test_admin_restores_an_archived_offer(): void
    {
        $offer = $this->makeOffer('Manioc frais', 'archivee');

        Livewire::actingAs($this->admin)->test(CropOffers::class)
            ->call('restore', $offer);

        $this->assertSame('publiee', $offer->fresh()->status->value);
    }

    public function test_a_learner_cannot_access_the_screen(): void
    {
        $learner = User::factory()->create();

        $this->actingAs($learner)->get(route('admin.crop-offers'))->assertForbidden();
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.crop-offers'))->assertRedirect(route('login'));
    }
}

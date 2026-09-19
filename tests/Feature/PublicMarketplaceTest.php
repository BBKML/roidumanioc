<?php

namespace Tests\Feature;

use App\Livewire\Public\Needs;
use App\Livewire\Public\Producers;
use App\Models\BuyerNeed;
use App\Models\BuyerProfile;
use App\Models\CropOffer;
use App\Models\ProducerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PublicMarketplaceTest extends TestCase
{
    use RefreshDatabase;

    protected function producerWithOffer(array $userAttrs = [], array $offerAttrs = []): ProducerProfile
    {
        $user = User::factory()->create($userAttrs);
        $profile = ProducerProfile::create([
            'user_id' => $user->id, 'business_name' => 'Ferme Kouassi',
            'zone' => 'Daloa, Haut-Sassandra', 'activity_type' => 'recolte',
        ]);
        CropOffer::create(array_merge([
            'producer_profile_id' => $profile->id, 'product_name' => 'Manioc frais',
            'quantity' => 10, 'unit' => 'kg', 'location' => 'Daloa',
            'status' => 'publiee', 'is_available' => true,
        ], $offerAttrs));

        return $profile;
    }

    protected function buyerWithNeed(array $userAttrs = [], array $needAttrs = []): BuyerProfile
    {
        $user = User::factory()->create($userAttrs);
        $profile = BuyerProfile::create([
            'user_id' => $user->id, 'buyer_type' => 'transformateur', 'zone' => 'Abidjan, Cocody',
        ]);
        BuyerNeed::create(array_merge([
            'buyer_profile_id' => $profile->id, 'product_wanted' => 'Manioc frais',
            'quantity' => 50, 'unit' => 'kg', 'location' => 'Abidjan', 'frequency' => 'ponctuel',
            'status' => 'ouvert',
        ], $needAttrs));

        return $profile;
    }

    /* ------------------------------------------------------------------ */

    public function test_anonymous_visitor_sees_producers_with_a_published_offer(): void
    {
        $this->producerWithOffer();

        $this->get(route('producers.index'))
            ->assertOk()
            ->assertSee('Ferme Kouassi')
            ->assertSee('Manioc frais');
    }

    public function test_producer_without_any_published_offer_is_not_listed(): void
    {
        $user = User::factory()->create();
        ProducerProfile::create([
            'user_id' => $user->id, 'business_name' => 'Ferme sans offre',
            'zone' => 'Bouaké', 'activity_type' => 'bouture',
        ]);

        $this->get(route('producers.index'))
            ->assertOk()
            ->assertDontSee('Ferme sans offre')
            ->assertSee('Aucun producteur ne correspond');
    }

    public function test_producer_with_only_a_draft_offer_is_not_listed(): void
    {
        $this->producerWithOffer(offerAttrs: ['status' => 'brouillon']);

        $this->get(route('producers.index'))
            ->assertOk()
            ->assertDontSee('Ferme Kouassi');
    }

    public function test_producer_with_only_an_unavailable_offer_is_not_listed(): void
    {
        $this->producerWithOffer(offerAttrs: ['is_available' => false]);

        $this->get(route('producers.index'))
            ->assertOk()
            ->assertDontSee('Ferme Kouassi');
    }

    public function test_producer_belonging_to_a_suspended_account_is_never_listed(): void
    {
        $this->producerWithOffer(userAttrs: ['status' => 'suspendu']);

        $this->get(route('producers.index'))
            ->assertOk()
            ->assertDontSee('Ferme Kouassi');
    }

    public function test_verified_producer_shows_the_badge(): void
    {
        $profile = $this->producerWithOffer();
        $profile->forceFill(['verified_at' => now()])->save();

        $this->get(route('producers.index'))
            ->assertOk()
            ->assertSee('Vérifié');
    }

    public function test_producers_can_be_filtered_by_zone_activity_type_and_verified(): void
    {
        $verified = $this->producerWithOffer();
        $verified->forceFill(['verified_at' => now()])->save();

        $other = User::factory()->create();
        $otherProfile = ProducerProfile::create([
            'user_id' => $other->id, 'business_name' => 'Boutures du Nord',
            'zone' => 'Korhogo', 'activity_type' => 'bouture',
        ]);
        CropOffer::create([
            'producer_profile_id' => $otherProfile->id, 'product_name' => 'Boutures améliorées',
            'quantity' => 100, 'unit' => 'sac', 'location' => 'Korhogo',
            'status' => 'publiee', 'is_available' => true,
        ]);

        Livewire::test(Producers::class)
            ->assertSee('Ferme Kouassi')
            ->assertSee('Boutures du Nord')
            ->set('zone', 'Korhogo')
            ->assertDontSee('Ferme Kouassi')
            ->assertSee('Boutures du Nord')
            ->set('zone', '')
            ->set('activityType', 'recolte')
            ->assertSee('Ferme Kouassi')
            ->assertDontSee('Boutures du Nord')
            ->set('activityType', '')
            ->set('verifiedOnly', true)
            ->assertSee('Ferme Kouassi')
            ->assertDontSee('Boutures du Nord')
            ->set('verifiedOnly', false)
            ->set('product', 'Boutures améliorées')
            ->assertDontSee('Ferme Kouassi')
            ->assertSee('Boutures du Nord');
    }

    public function test_producers_zone_and_product_filters_are_populated_from_published_offers(): void
    {
        $this->producerWithOffer();

        $draftOwner = $this->producerWithOffer(offerAttrs: ['product_name' => 'Farine hors catalogue', 'status' => 'brouillon']);

        $this->get(route('producers.index'))
            ->assertOk()
            ->assertSee('Daloa, Haut-Sassandra')
            ->assertSee('Manioc frais')
            ->assertDontSee('Farine hors catalogue');
    }

    public function test_no_personal_contact_information_is_exposed_on_the_producers_page(): void
    {
        $user = User::factory()->create(['phone' => '0700000099', 'email' => 'secret-producer@example.com']);
        $profile = ProducerProfile::create([
            'user_id' => $user->id, 'business_name' => 'Ferme Kouassi',
            'zone' => 'Daloa', 'activity_type' => 'recolte',
        ]);
        CropOffer::create([
            'producer_profile_id' => $profile->id, 'product_name' => 'Manioc frais',
            'quantity' => 10, 'unit' => 'kg', 'location' => 'Daloa',
            'status' => 'publiee', 'is_available' => true,
        ]);

        $this->get(route('producers.index'))
            ->assertOk()
            ->assertDontSee('0700000099')
            ->assertDontSee('secret-producer@example.com');
    }

    /* ------------------------------------------------------------------ */

    public function test_anonymous_visitor_sees_open_needs(): void
    {
        $this->buyerWithNeed();

        $this->get(route('needs.index'))
            ->assertOk()
            ->assertSee('Manioc frais');
    }

    public function test_need_that_is_not_open_is_not_listed(): void
    {
        $this->buyerWithNeed(needAttrs: ['status' => 'satisfait']);

        $this->get(route('needs.index'))
            ->assertOk()
            ->assertDontSee('Manioc frais')
            ->assertSee('Aucun besoin ne correspond');
    }

    public function test_need_belonging_to_a_suspended_account_is_never_listed(): void
    {
        $this->buyerWithNeed(userAttrs: ['status' => 'suspendu']);

        $this->get(route('needs.index'))
            ->assertOk()
            ->assertDontSee('Manioc frais');
    }

    public function test_needs_can_be_filtered_by_search_and_zone(): void
    {
        $this->buyerWithNeed();
        $other = User::factory()->create();
        $otherProfile = BuyerProfile::create([
            'user_id' => $other->id, 'buyer_type' => 'restaurant', 'zone' => 'Bouaké',
        ]);
        BuyerNeed::create([
            'buyer_profile_id' => $otherProfile->id, 'product_wanted' => 'Attiéké',
            'quantity' => 20, 'unit' => 'sac', 'location' => 'Bouaké', 'frequency' => 'recurrent',
            'status' => 'ouvert',
        ]);

        // Les cartes de résultat portent le produit ET sa quantité (« 50,00 Kg »/« 20,00 Sac ») :
        // on distingue une carte affichée d'une simple option des listes déroulantes « produit »/
        // « zone » (qui, elles, listent toujours toutes les valeurs possibles, indépendamment du
        // filtre courant) en vérifiant ce couple plutôt que le seul nom de produit.
        Livewire::test(Needs::class)
            ->assertSee('50,00 Kg')
            ->assertSee('20,00 Sac')
            ->set('search', 'Attiéké')
            ->assertDontSee('50,00 Kg')
            ->assertSee('20,00 Sac')
            ->set('search', '')
            ->set('zone', 'Abidjan')
            ->assertSee('50,00 Kg')
            ->assertDontSee('20,00 Sac')
            ->set('zone', '')
            ->set('product', 'Attiéké')
            ->assertDontSee('50,00 Kg')
            ->assertSee('20,00 Sac');
    }

    public function test_no_personal_contact_information_is_exposed_on_the_needs_page(): void
    {
        $user = User::factory()->create(['phone' => '0700000088', 'email' => 'secret-buyer@example.com']);
        $profile = BuyerProfile::create([
            'user_id' => $user->id, 'buyer_type' => 'transformateur', 'zone' => 'Abidjan',
        ]);
        BuyerNeed::create([
            'buyer_profile_id' => $profile->id, 'product_wanted' => 'Manioc frais',
            'quantity' => 50, 'unit' => 'kg', 'location' => 'Abidjan', 'frequency' => 'ponctuel',
            'status' => 'ouvert',
        ]);

        $this->get(route('needs.index'))
            ->assertOk()
            ->assertDontSee('0700000088')
            ->assertDontSee('secret-buyer@example.com');
    }

    /* ------------------------------------------------------------------ */

    public function test_home_page_shows_the_producer_and_buyer_ctas(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Devenir producteur')
            ->assertSee('Devenir acheteur')
            ->assertSee(route('producers.index'), false)
            ->assertSee(route('needs.index'), false);
    }

    public function test_producers_pagination_shows_more_than_one_page(): void
    {
        for ($i = 1; $i <= 13; $i++) {
            $user = User::factory()->create();
            $profile = ProducerProfile::create([
                'user_id' => $user->id, 'business_name' => "Ferme $i",
                'zone' => 'Daloa', 'activity_type' => 'recolte',
            ]);
            CropOffer::create([
                'producer_profile_id' => $profile->id, 'product_name' => 'Manioc',
                'quantity' => 10, 'unit' => 'kg', 'location' => 'Daloa',
                'status' => 'publiee', 'is_available' => true,
            ]);
        }

        Livewire::test(Producers::class)
            ->assertViewHas('producers', fn ($producers) => $producers->total() === 13 && $producers->lastPage() === 2);
    }
}

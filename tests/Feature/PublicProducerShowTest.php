<?php

namespace Tests\Feature;

use App\Models\CropOffer;
use App\Models\ProducerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicProducerShowTest extends TestCase
{
    use RefreshDatabase;

    protected function producer(array $userAttrs = [], array $profileAttrs = []): ProducerProfile
    {
        $user = User::factory()->create($userAttrs);

        return ProducerProfile::create(array_merge([
            'user_id' => $user->id, 'business_name' => 'Ferme Kouassi',
            'bio' => 'Producteur familial depuis trois générations.',
            'zone' => 'Daloa, Haut-Sassandra', 'activity_type' => 'recolte',
        ], $profileAttrs));
    }

    protected function offer(ProducerProfile $profile, array $attrs = []): CropOffer
    {
        return CropOffer::create(array_merge([
            'producer_profile_id' => $profile->id, 'product_name' => 'Manioc frais',
            'quantity' => 10, 'unit' => 'kg', 'location' => 'Daloa',
            'status' => 'publiee', 'is_available' => true,
        ], $attrs));
    }

    public function test_the_page_shows_the_profile_and_every_published_offer_without_the_catalog_cap_of_three(): void
    {
        $profile = $this->producer();
        $this->offer($profile, ['product_name' => 'Manioc frais']);
        $this->offer($profile, ['product_name' => 'Attiéké artisanal']);
        $this->offer($profile, ['product_name' => 'Gari fin']);
        $this->offer($profile, ['product_name' => 'Boutures améliorées']);

        $this->get(route('producers.show', $profile))
            ->assertOk()
            ->assertSee('Ferme Kouassi')
            ->assertSee('Producteur familial depuis trois générations.')
            ->assertSee('Daloa, Haut-Sassandra')
            ->assertSee('Manioc frais')
            ->assertSee('Attiéké artisanal')
            ->assertSee('Gari fin')
            ->assertSee('Boutures améliorées');
    }

    public function test_a_draft_or_unavailable_offer_is_never_shown_on_the_profile(): void
    {
        $profile = $this->producer();
        $this->offer($profile, ['product_name' => 'Manioc frais']);
        $this->offer($profile, ['product_name' => 'Brouillon caché', 'status' => 'brouillon']);
        $this->offer($profile, ['product_name' => 'Rupture cachée', 'is_available' => false]);

        $this->get(route('producers.show', $profile))
            ->assertOk()
            ->assertSee('Manioc frais')
            ->assertDontSee('Brouillon caché')
            ->assertDontSee('Rupture cachée');
    }

    public function test_a_producer_belonging_to_a_suspended_account_404s(): void
    {
        $profile = $this->producer(['status' => 'suspendu']);
        $this->offer($profile);

        $this->get(route('producers.show', $profile))->assertNotFound();
    }

    public function test_a_producer_without_any_offer_still_has_a_visible_profile(): void
    {
        $profile = $this->producer();

        $this->get(route('producers.show', $profile))
            ->assertOk()
            ->assertSee('Ferme Kouassi')
            ->assertSee('Aucune offre publiée pour le moment.');
    }

    public function test_the_verified_badge_shows_only_when_verified(): void
    {
        $profile = $this->producer();
        $this->offer($profile);

        $this->get(route('producers.show', $profile))
            ->assertOk()
            ->assertDontSee('Vérifié');

        $profile->forceFill(['verified_at' => now()])->save();

        $this->get(route('producers.show', $profile))
            ->assertOk()
            ->assertSee('Vérifié');
    }

    public function test_no_personal_contact_information_is_exposed_on_the_profile(): void
    {
        $profile = $this->producer(['phone' => '0700000099', 'email' => 'secret-producer@example.com']);
        $this->offer($profile);

        $this->get(route('producers.show', $profile))
            ->assertOk()
            ->assertDontSee('0700000099')
            ->assertDontSee('secret-producer@example.com');
    }

    public function test_a_logged_in_buyer_can_toggle_the_favorite_from_the_profile(): void
    {
        $profile = $this->producer();
        $this->offer($profile);
        $buyer = User::factory()->create();

        $this->assertFalse($buyer->favoriteProducers()->where('producer_profiles.id', $profile->id)->exists());

        $this->actingAs($buyer)->post(route('producers.favorite.toggle', $profile))->assertRedirect();

        $this->assertTrue($buyer->fresh()->favoriteProducers()->where('producer_profiles.id', $profile->id)->exists());
    }

    public function test_the_catalog_card_links_to_the_profile_page(): void
    {
        $profile = $this->producer();
        $this->offer($profile);

        $this->get(route('producers.index'))
            ->assertOk()
            ->assertSee(route('producers.show', $profile), false);
    }

    /* ------------------------------------------------------------------ *
     |  Bouton « Passer une commande » (§1) — ajouté à côté de « Négocier »,
     |  sans jamais le remplacer (parcours de commande structuré, distinct
     |  de la mise en relation par chat).
     * ------------------------------------------------------------------ */

    public function test_a_guest_is_invited_to_log_in_instead_of_seeing_the_order_button(): void
    {
        $profile = $this->producer();
        $offer = $this->offer($profile);

        $this->get(route('producers.show', $profile))
            ->assertOk()
            ->assertSee('Négocier')
            ->assertDontSee(route('learner.buyer.crop-orders.create', $offer), false)
            ->assertSee(route('login'), false);
    }

    public function test_a_logged_in_buyer_sees_the_order_button_next_to_negotiate(): void
    {
        $profile = $this->producer();
        $offer = $this->offer($profile);
        $buyerUser = User::factory()->create();
        \App\Models\BuyerProfile::create(['user_id' => $buyerUser->id, 'buyer_type' => 'transformateur', 'zone' => 'Abidjan']);

        $this->actingAs($buyerUser)->get(route('producers.show', $profile))
            ->assertOk()
            ->assertSee('Négocier')
            ->assertSee('Passer une commande')
            ->assertSee(route('learner.buyer.crop-orders.create', $offer), false);
    }

    public function test_a_logged_in_user_without_a_buyer_profile_is_invited_to_activate_it(): void
    {
        $profile = $this->producer();
        $this->offer($profile);
        $plainUser = User::factory()->create();

        $this->actingAs($plainUser)->get(route('producers.show', $profile))
            ->assertOk()
            ->assertSee('Négocier')
            ->assertDontSee('Passer une commande')
            ->assertSee(route('learner.buyer'), false);
    }
}

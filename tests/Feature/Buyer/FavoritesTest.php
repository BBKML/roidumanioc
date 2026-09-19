<?php

namespace Tests\Feature\Buyer;

use App\Livewire\Buyer\Favorites;
use App\Livewire\Public\Producers;
use App\Models\ProducerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/** Favoris d'un acheteur sur des producteurs (§11, Phase 12). */
class FavoritesTest extends TestCase
{
    use RefreshDatabase;

    protected function makeProducerProfile(): ProducerProfile
    {
        $owner = User::factory()->create();

        return ProducerProfile::create([
            'user_id' => $owner->id, 'business_name' => 'Ferme Kouassi',
            'zone' => 'Daloa', 'activity_type' => 'recolte',
        ]);
    }

    public function test_a_guest_is_redirected_to_login_when_trying_to_favorite(): void
    {
        $producer = $this->makeProducerProfile();

        $this->post(route('producers.favorite.toggle', $producer))
            ->assertRedirect(route('login'));

        $this->assertSame(0, DB::table('favorites')->count());
    }

    public function test_an_authenticated_user_can_toggle_a_favorite(): void
    {
        $user = User::factory()->create();
        $producer = $this->makeProducerProfile();

        $this->actingAs($user)->post(route('producers.favorite.toggle', $producer))
            ->assertRedirect();
        $this->assertTrue($user->favoriteProducers()->whereKey($producer->id)->exists());

        // Idempotent dans les deux sens : un second clic retire le favori.
        $this->actingAs($user)->post(route('producers.favorite.toggle', $producer))
            ->assertRedirect();
        $this->assertFalse($user->favoriteProducers()->whereKey($producer->id)->exists());
    }

    public function test_the_public_catalog_reflects_the_favorite_state(): void
    {
        $user = User::factory()->create();
        $producer = $this->makeProducerProfile();
        $producer->cropOffers()->create([
            'product_name' => 'Manioc frais', 'quantity' => 10, 'unit' => 'kg',
            'location' => 'Daloa', 'status' => 'publiee', 'is_available' => true,
        ]);

        Livewire::actingAs($user)->test(Producers::class)
            ->assertViewHas('favoriteIds', [])
            ->assertDontSee('is-fav');

        $user->favoriteProducers()->attach($producer->id);

        Livewire::actingAs($user)->test(Producers::class)
            ->assertViewHas('favoriteIds', [$producer->id])
            ->assertSee('is-fav');
    }

    public function test_the_favorites_page_lists_and_removes_favorites(): void
    {
        $user = User::factory()->create();
        $producer = $this->makeProducerProfile();
        $user->favoriteProducers()->attach($producer->id);

        Livewire::actingAs($user)->test(Favorites::class)
            ->assertSee('Ferme Kouassi')
            ->call('unfavorite', $producer->id);

        $this->assertFalse($user->favoriteProducers()->whereKey($producer->id)->exists());
    }

    public function test_a_user_only_sees_their_own_favorites(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $producer = $this->makeProducerProfile();
        $other->favoriteProducers()->attach($producer->id);

        Livewire::actingAs($user)->test(Favorites::class)
            ->assertDontSee('Ferme Kouassi');
    }
}

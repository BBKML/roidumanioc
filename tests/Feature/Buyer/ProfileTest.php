<?php

namespace Tests\Feature\Buyer;

use App\Livewire\Learner\BuyerProfile;
use App\Livewire\Learner\ProducerProfile;
use App\Models\BuyerProfile as BuyerProfileModel;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_learner_activates_a_buyer_profile(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(BuyerProfile::class)
            ->set('company_name', 'Attiéké Express')
            ->set('buyer_type', 'transformateur')
            ->set('zone', 'Abidjan, Cocody')
            ->set('acceptedTerms', true)
            ->call('save')
            ->assertHasNoErrors();

        $profile = BuyerProfileModel::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('Attiéké Express', $profile->company_name);
        $this->assertSame('transformateur', $profile->buyer_type->value);
        $this->assertTrue($user->fresh()->isBuyer());
        $this->assertTrue($user->fresh()->isApprenant());
        $this->assertNotNull($profile->terms_accepted_at);
    }

    /**
     * Audit UX (Phase 2/3) : "Profil acheteur" partout, plus de mélange avec
     * "Espace acheteur" (titre de page vs titre de la carte du formulaire). Le bouton
     * de première activation dit désormais "Devenir acheteur" (formulation unifiée),
     * plus "Activer mon espace acheteur".
     */
    public function test_the_page_consistently_says_profil_acheteur(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)->test(BuyerProfile::class)
            ->assertSee('Profil acheteur')
            ->assertDontSee('Espace acheteur')
            ->assertSee('Devenir acheteur')
            ->assertDontSee('Activer mon espace acheteur');

        $component->set('buyer_type', 'transformateur')
            ->set('zone', 'Abidjan')
            ->set('acceptedTerms', true)
            ->call('save')
            ->assertDispatched('notify', message: 'Profil acheteur activé.');
    }

    public function test_activation_is_blocked_without_accepting_the_terms(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(BuyerProfile::class)
            ->set('buyer_type', 'restaurant')
            ->set('zone', 'Abidjan')
            ->call('save')
            ->assertHasErrors('acceptedTerms');

        $this->assertNull(BuyerProfileModel::where('user_id', $user->id)->first());
    }

    public function test_buyer_type_and_zone_are_required_but_company_name_is_optional(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(BuyerProfile::class)
            ->set('buyer_type', '')
            ->set('zone', '')
            ->call('save')
            ->assertHasErrors(['buyer_type', 'zone']);

        Livewire::actingAs($user)->test(BuyerProfile::class)
            ->set('buyer_type', 'restaurant')
            ->set('zone', 'Abidjan')
            ->set('acceptedTerms', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull(BuyerProfileModel::where('user_id', $user->id)->firstOrFail()->company_name);
    }

    public function test_learner_updates_their_existing_buyer_profile(): void
    {
        $user = User::factory()->create();
        BuyerProfileModel::create([
            'user_id' => $user->id, 'buyer_type' => 'grossiste', 'zone' => 'Yamoussoukro',
        ]);

        Livewire::actingAs($user)->test(BuyerProfile::class)
            ->assertSet('buyer_type', 'grossiste')
            ->set('company_name', 'Nouvelle enseigne')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Nouvelle enseigne', $user->fresh()->buyerProfile->company_name);
    }

    public function test_a_user_cannot_have_two_buyer_profiles(): void
    {
        $user = User::factory()->create();
        BuyerProfileModel::create(['user_id' => $user->id, 'buyer_type' => 'grossiste', 'zone' => 'Yamoussoukro']);

        $this->expectException(QueryException::class);
        BuyerProfileModel::create(['user_id' => $user->id, 'buyer_type' => 'distributeur', 'zone' => 'Bouaké']);
    }

    public function test_buyer_nav_tab_and_cta_reflect_activation_state(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('learner.dashboard'))
            ->assertOk()
            ->assertSee('Devenir acheteur');

        BuyerProfileModel::create(['user_id' => $user->id, 'buyer_type' => 'commercant', 'zone' => 'Abidjan']);

        $this->actingAs($user->fresh())->get(route('learner.dashboard'))
            ->assertOk()
            ->assertSee('Profil acheteur')
            ->assertDontSee('Devenir acheteur');
    }

    public function test_a_user_can_be_learner_producer_and_buyer_at_the_same_time(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(ProducerProfile::class)
            ->set('business_name', 'Ferme Kouassi')
            ->set('zone', 'Daloa')
            ->set('activity_type', 'recolte')
            ->set('acceptedTerms', true)
            ->call('save')
            ->assertHasNoErrors();

        Livewire::actingAs($user)->test(BuyerProfile::class)
            ->set('buyer_type', 'restaurant')
            ->set('zone', 'Abidjan')
            ->set('acceptedTerms', true)
            ->call('save')
            ->assertHasNoErrors();

        $fresh = $user->fresh();
        $this->assertTrue($fresh->isApprenant());
        $this->assertTrue($fresh->isProducer());
        $this->assertTrue($fresh->isBuyer());
    }

    public function test_saving_the_profile_is_rate_limited(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 10; $i++) {
            Livewire::actingAs($user->fresh())->test(BuyerProfile::class)
                ->set('buyer_type', 'restaurant')
                ->set('zone', "Abidjan $i")
                ->set('acceptedTerms', true) // uniquement requis à la 1re activation (i=0), inoffensif ensuite
                ->call('save')
                ->assertHasNoErrors();
        }

        Livewire::actingAs($user->fresh())->test(BuyerProfile::class)
            ->set('buyer_type', 'restaurant')
            ->set('zone', 'Une de trop')
            ->call('save')
            ->assertHasErrors('company_name');

        $this->assertNotSame('Une de trop', BuyerProfileModel::where('user_id', $user->id)->firstOrFail()->zone);
    }
}

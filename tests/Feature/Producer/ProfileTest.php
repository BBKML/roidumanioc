<?php

namespace Tests\Feature\Producer;

use App\Livewire\Learner\ProducerProfile;
use App\Models\ProducerProfile as ProducerProfileModel;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_learner_activates_a_producer_profile(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(ProducerProfile::class)
            ->set('business_name', 'Ferme Kouassi')
            ->set('zone', 'Daloa, Haut-Sassandra')
            ->set('activity_type', 'recolte')
            ->set('bio', 'Producteur de manioc depuis 2018.')
            ->set('acceptedTerms', true)
            ->call('save')
            ->assertHasNoErrors();

        $profile = ProducerProfileModel::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('Ferme Kouassi', $profile->business_name);
        $this->assertSame('recolte', $profile->activity_type->value);
        $this->assertTrue($user->fresh()->isProducer());
        $this->assertTrue($user->fresh()->isApprenant());
        $this->assertNotNull($profile->terms_accepted_at);
    }

    /**
     * Audit UX (Phase 2/3) : "Profil producteur" partout, plus de mélange avec
     * "Espace producteur" (titre de page vs titre de la carte du formulaire). Le bouton
     * de première activation dit désormais "Devenir producteur" (formulation unifiée),
     * plus "Activer mon espace producteur".
     */
    public function test_the_page_consistently_says_profil_producteur(): void
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)->test(ProducerProfile::class)
            ->assertSee('Profil producteur')
            ->assertDontSee('Espace producteur')
            ->assertSee('Devenir producteur')
            ->assertDontSee('Activer mon espace producteur');

        $component->set('business_name', 'Ferme Kouassi')
            ->set('zone', 'Daloa')
            ->set('activity_type', 'recolte')
            ->set('acceptedTerms', true)
            ->call('save')
            ->assertDispatched('notify', message: 'Profil producteur activé.');
    }

    public function test_activation_is_blocked_without_accepting_the_terms(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(ProducerProfile::class)
            ->set('business_name', 'Ferme Kouassi')
            ->set('zone', 'Daloa')
            ->set('activity_type', 'recolte')
            ->call('save')
            ->assertHasErrors('acceptedTerms');

        $this->assertNull(ProducerProfileModel::where('user_id', $user->id)->first());
    }

    public function test_business_name_zone_and_activity_type_are_required(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(ProducerProfile::class)
            ->set('business_name', '')
            ->set('zone', '')
            ->set('activity_type', '')
            ->call('save')
            ->assertHasErrors(['business_name', 'zone', 'activity_type']);
    }

    public function test_learner_updates_their_existing_producer_profile(): void
    {
        $user = User::factory()->create();
        ProducerProfileModel::create([
            'user_id' => $user->id, 'business_name' => 'Ancien nom',
            'zone' => 'Bouaké', 'activity_type' => 'bouture',
        ]);

        Livewire::actingAs($user)->test(ProducerProfile::class)
            ->assertSet('business_name', 'Ancien nom')
            ->set('business_name', 'Nouveau nom')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Nouveau nom', $user->fresh()->producerProfile->business_name);
    }

    public function test_a_user_cannot_have_two_producer_profiles(): void
    {
        $user = User::factory()->create();
        ProducerProfileModel::create([
            'user_id' => $user->id, 'business_name' => 'Ferme A',
            'zone' => 'Bouaké', 'activity_type' => 'bouture',
        ]);

        $this->expectException(QueryException::class);
        ProducerProfileModel::create([
            'user_id' => $user->id, 'business_name' => 'Ferme B',
            'zone' => 'Bouaké', 'activity_type' => 'bouture',
        ]);
    }

    public function test_learner_uploads_a_logo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(ProducerProfile::class)
            ->set('business_name', 'Ferme Kouassi')
            ->set('zone', 'Daloa')
            ->set('activity_type', 'transforme')
            ->set('logo', UploadedFile::fake()->image('logo.jpg'))
            ->set('acceptedTerms', true)
            ->call('save')
            ->assertHasNoErrors();

        $profile = ProducerProfileModel::where('user_id', $user->id)->firstOrFail();
        $this->assertNotNull($profile->logo_path);
        Storage::disk('public')->assertExists($profile->logo_path);
    }

    public function test_verified_at_and_verified_by_cannot_be_set_by_the_user(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(ProducerProfile::class)
            ->set('business_name', 'Ferme Kouassi')
            ->set('zone', 'Daloa')
            ->set('activity_type', 'intrant')
            ->set('acceptedTerms', true)
            ->call('save')
            ->assertHasNoErrors();

        $profile = ProducerProfileModel::where('user_id', $user->id)->firstOrFail();
        $this->assertNull($profile->verified_at);
        $this->assertNull($profile->verified_by);
    }

    public function test_a_user_cannot_update_another_users_producer_profile(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        ProducerProfileModel::create([
            'user_id' => $owner->id, 'business_name' => 'Ferme du propriétaire',
            'zone' => 'Bouaké', 'activity_type' => 'bouture',
        ]);

        // L'intrus n'a pas encore de profil : `save()` en créerait un nouveau pour lui-même,
        // la policy ne peut donc être testée qu'au travers de son propre profil (voir ci-dessus).
        // On vérifie ici que chaque utilisateur ne modifie bien que SON profil.
        Livewire::actingAs($intruder)->test(ProducerProfile::class)
            ->set('business_name', 'Ferme de l\'intrus')
            ->set('zone', 'Korhogo')
            ->set('activity_type', 'recolte')
            ->set('acceptedTerms', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Ferme du propriétaire', $owner->fresh()->producerProfile->business_name);
        $this->assertSame('Ferme de l\'intrus', $intruder->fresh()->producerProfile->business_name);
    }

    public function test_producer_nav_tab_and_cta_reflect_activation_state(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('learner.dashboard'))
            ->assertOk()
            ->assertSee('Devenir producteur');

        ProducerProfileModel::create([
            'user_id' => $user->id, 'business_name' => 'Ferme Kouassi',
            'zone' => 'Daloa', 'activity_type' => 'recolte',
        ]);

        $this->actingAs($user->fresh())->get(route('learner.dashboard'))
            ->assertOk()
            ->assertSee('Profil producteur')
            ->assertDontSee('Devenir producteur');
    }

    public function test_saving_the_profile_is_rate_limited(): void
    {
        $user = User::factory()->create();

        // Chaque itération repart d'un User frais : Auth::user()->producerProfile mis en
        // cache (à null, puis sur l'instance créée) sur le même objet PHP fausserait les
        // sauvegardes suivantes (même piège de cache de relation Livewire que documenté
        // dans CLAUDE.md pour Connect\Conversation).
        for ($i = 0; $i < 10; $i++) {
            Livewire::actingAs($user->fresh())->test(ProducerProfile::class)
                ->set('business_name', "Ferme Kouassi $i")
                ->set('zone', 'Daloa')
                ->set('activity_type', 'recolte')
                ->set('acceptedTerms', true) // uniquement requis à la 1re activation (i=0), inoffensif ensuite
                ->call('save')
                ->assertHasNoErrors();
        }

        Livewire::actingAs($user->fresh())->test(ProducerProfile::class)
            ->set('business_name', 'Une de trop')
            ->set('zone', 'Daloa')
            ->set('activity_type', 'recolte')
            ->call('save')
            ->assertHasErrors('business_name');

        $this->assertNotSame('Une de trop', ProducerProfileModel::where('user_id', $user->id)->firstOrFail()->business_name);
    }
}

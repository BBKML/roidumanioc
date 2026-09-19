<?php

namespace Tests\Feature\Account;

use App\Livewire\Account\Settings;
use App\Models\ProducerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AccountSettingsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Audit UX (Phase 3) : "Devenir producteur/acheteur" avant activation (formulation
     * unifiée avec le reste de l'app), "Profil producteur/acheteur" après — jamais
     * "Je suis..."/"Activer mon profil...".
     */
    public function test_producer_and_buyer_ctas_use_the_unified_wording(): void
    {
        $user = User::factory()->create(['role' => 'apprenant', 'status' => 'actif']);

        Livewire::actingAs($user)->test(Settings::class)
            ->assertSee('Devenir producteur')
            ->assertSee('Devenir acheteur')
            ->assertDontSee('Activer mon profil producteur')
            ->assertDontSee('Activer mon profil acheteur');

        ProducerProfile::create([
            'user_id' => $user->id, 'business_name' => 'Ferme Kouassi', 'zone' => 'Daloa', 'activity_type' => 'recolte',
        ]);

        // $user garde en mémoire sa relation producerProfile (null) chargée par le premier
        // rendu ci-dessus — il faut un modèle frais pour que Livewire voie le profil créé.
        Livewire::actingAs($user->fresh())->test(Settings::class)
            ->assertSee('Votre profil producteur est actif')
            ->assertSee('Modifier mon profil producteur')
            ->assertDontSee('Devenir producteur');
    }

    public function test_learner_updates_profile_and_password(): void
    {
        $user = User::factory()->create([
            'role' => 'apprenant', 'status' => 'actif',
            'password' => Hash::make('ancien-mot-de-passe'), 'password_changed_at' => now(),
        ]);

        Livewire::actingAs($user)->test(Settings::class)
            ->set('name', 'Nouveau Nom')
            ->set('phone', '0700000000')
            ->call('updateProfile')
            ->assertHasNoErrors();

        $this->assertSame('Nouveau Nom', $user->fresh()->name);

        Livewire::actingAs($user)->test(Settings::class)
            ->set('current_password', 'ancien-mot-de-passe')
            ->set('password', 'nouveau-passe-2026')
            ->set('password_confirmation', 'nouveau-passe-2026')
            ->call('updatePassword')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('nouveau-passe-2026', $user->fresh()->password));
        $this->assertNotNull($user->fresh()->password_changed_at);
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        $user = User::factory()->create(['password' => Hash::make('bon'), 'password_changed_at' => now()]);

        Livewire::actingAs($user)->test(Settings::class)
            ->set('current_password', 'mauvais')
            ->set('password', 'peu-importe-mais-2026')
            ->set('password_confirmation', 'peu-importe-mais-2026')
            ->call('updatePassword')
            ->assertHasErrors('current_password');
    }

    public function test_last_active_admin_cannot_delete_their_account(): void
    {
        $admin = User::factory()->admin()->create(['password' => Hash::make('secret1234'), 'password_changed_at' => now()]);

        Livewire::actingAs($admin)->test(Settings::class)
            ->set('delete_password', 'secret1234')
            ->call('deleteAccount')
            ->assertForbidden();

        $this->assertModelExists($admin);
    }

    public function test_learner_can_delete_their_account_with_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('secret1234'), 'password_changed_at' => now()]);

        Livewire::actingAs($user)->test(Settings::class)
            ->set('delete_password', 'secret1234')
            ->call('deleteAccount')
            ->assertRedirect(route('home'));

        $this->assertModelMissing($user);
    }

    public function test_account_page_uses_the_right_layout_per_role(): void
    {
        $admin = User::factory()->admin()->create();
        $learner = User::factory()->create(['role' => 'apprenant']);

        $this->actingAs($admin)->get(route('account.edit'))->assertOk()->assertSee('Rôle & accès');
        $this->actingAs($learner)->get(route('account.edit'))->assertOk()->assertSee('Résumé');
    }
}

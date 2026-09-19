<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Buyers;
use App\Models\BuyerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ConnectBuyersTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    protected function makeBuyer(string $name): BuyerProfile
    {
        $user = User::factory()->create();

        return BuyerProfile::create([
            'user_id' => $user->id, 'company_name' => $name, 'buyer_type' => 'transformateur', 'zone' => 'Abidjan',
        ]);
    }

    public function test_admin_sees_the_buyer_list(): void
    {
        $this->makeBuyer('Coopérative Abidjan');

        Livewire::actingAs($this->admin)->test(Buyers::class)
            ->assertSee('Coopérative Abidjan');
    }

    /** Audit UX (Phase 3) : tableau adapté en cartes sous 700px (même mécanisme). */
    public function test_the_table_is_mobile_ready(): void
    {
        $this->makeBuyer('Coopérative Abidjan');

        Livewire::actingAs($this->admin)->test(Buyers::class)
            ->assertSee('stack-mobile', false)
            ->assertSee('data-label="Zone"', false);
    }

    /** Audit architecture : état vide contextualisé + bouton de réinitialisation. */
    public function test_a_search_with_no_results_offers_to_reset_it(): void
    {
        $this->makeBuyer('Coopérative Abidjan');

        Livewire::actingAs($this->admin)->test(Buyers::class)
            ->set('search', 'Personne Ne Porte Ce Nom')
            ->assertSee('Aucun acheteur ne correspond à cette recherche.')
            ->call('resetFilters')
            ->assertSet('search', '')
            ->assertSet('filter', 'tous');
    }

    public function test_search_filters_by_company_name_or_zone(): void
    {
        $this->makeBuyer('Coopérative Abidjan');
        $this->makeBuyer('Grossiste Daloa');

        Livewire::actingAs($this->admin)->test(Buyers::class)
            ->set('search', 'Grossiste')
            ->assertSee('Grossiste Daloa')
            ->assertDontSee('Coopérative Abidjan');
    }

    public function test_admin_suspends_a_buyer_account(): void
    {
        $profile = $this->makeBuyer('Coopérative Abidjan');

        Livewire::actingAs($this->admin)->test(Buyers::class)
            ->call('toggleSuspend', $profile->id);

        $this->assertSame('suspendu', $profile->user->fresh()->status->value);
    }

    public function test_admin_reactivates_a_suspended_buyer_account(): void
    {
        $profile = $this->makeBuyer('Coopérative Abidjan');
        $profile->user->update(['status' => 'suspendu']);

        Livewire::actingAs($this->admin)->test(Buyers::class)
            ->call('toggleSuspend', $profile->id);

        $this->assertSame('actif', $profile->user->fresh()->status->value);
    }

    public function test_suspended_tab_only_shows_suspended_buyers(): void
    {
        $active = $this->makeBuyer('Coopérative Active');
        $suspended = $this->makeBuyer('Coopérative Suspendue');
        $suspended->user->update(['status' => 'suspendu']);

        Livewire::actingAs($this->admin)->test(Buyers::class)
            ->set('filter', 'suspendus')
            ->assertSee('Coopérative Suspendue')
            ->assertDontSee('Coopérative Active');
    }

    public function test_admin_cannot_suspend_their_own_buyer_account(): void
    {
        $profile = BuyerProfile::create([
            'user_id' => $this->admin->id, 'company_name' => 'Mon entreprise', 'buyer_type' => 'transformateur', 'zone' => 'Abidjan',
        ]);

        Livewire::actingAs($this->admin)->test(Buyers::class)
            ->call('toggleSuspend', $profile->id)
            ->assertForbidden();
    }

    public function test_a_learner_cannot_access_the_buyers_screen(): void
    {
        $learner = User::factory()->create();

        $this->actingAs($learner)->get(route('admin.buyers'))->assertForbidden();
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.buyers'))->assertRedirect(route('login'));
    }
}

<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\BuyerNeeds;
use App\Models\BuyerNeed;
use App\Models\BuyerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ConnectBuyerNeedsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected BuyerProfile $buyerProfile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();

        $buyer = User::factory()->create();
        $this->buyerProfile = BuyerProfile::create([
            'user_id' => $buyer->id, 'company_name' => 'Coopérative Abidjan', 'buyer_type' => 'transformateur', 'zone' => 'Abidjan',
        ]);
    }

    protected function makeNeed(string $product, string $status = 'ouvert'): BuyerNeed
    {
        return BuyerNeed::create([
            'buyer_profile_id' => $this->buyerProfile->id, 'product_wanted' => $product,
            'quantity' => 50, 'unit' => 'kg', 'location' => 'Abidjan', 'frequency' => 'ponctuel',
            'status' => $status,
        ]);
    }

    public function test_admin_sees_all_needs(): void
    {
        $this->makeNeed('Manioc frais');

        Livewire::actingAs($this->admin)->test(BuyerNeeds::class)
            ->assertSee('Manioc frais')
            ->assertSee('Coopérative Abidjan');
    }

    /** Audit UX (Phase 3) : tableau adapté en cartes sous 700px (même mécanisme). */
    public function test_the_table_is_mobile_ready(): void
    {
        $this->makeNeed('Manioc frais');

        Livewire::actingAs($this->admin)->test(BuyerNeeds::class)
            ->assertSee('stack-mobile', false)
            ->assertSee('data-label="Acheteur"', false);
    }

    /** Audit architecture : état vide contextualisé + bouton de réinitialisation. */
    public function test_a_search_with_no_results_offers_to_reset_it(): void
    {
        $this->makeNeed('Manioc frais');

        Livewire::actingAs($this->admin)->test(BuyerNeeds::class)
            ->set('search', 'Personne Ne Cherche Ça')
            ->assertSee('Aucun besoin ne correspond à cette recherche.')
            ->call('resetFilters')
            ->assertSet('search', '')
            ->assertSet('filter', 'tous');
    }

    public function test_search_filters_by_product_or_buyer(): void
    {
        $this->makeNeed('Manioc frais');
        $this->makeNeed('Igname');

        Livewire::actingAs($this->admin)->test(BuyerNeeds::class)
            ->set('search', 'Igname')
            ->assertSee('Igname')
            ->assertDontSee('Manioc frais');
    }

    public function test_filter_tabs_scope_by_status(): void
    {
        $this->makeNeed('Manioc frais', 'ouvert');
        $this->makeNeed('Manioc fermé', 'ferme');

        Livewire::actingAs($this->admin)->test(BuyerNeeds::class)
            ->set('filter', 'ferme')
            ->assertSee('Manioc fermé')
            ->assertDontSee('Manioc frais');
    }

    public function test_admin_closes_a_need(): void
    {
        $need = $this->makeNeed('Manioc frais');

        Livewire::actingAs($this->admin)->test(BuyerNeeds::class)
            ->call('close', $need);

        $this->assertSame('ferme', $need->fresh()->status->value);
    }

    public function test_admin_reopens_a_closed_need(): void
    {
        $need = $this->makeNeed('Manioc frais', 'ferme');

        Livewire::actingAs($this->admin)->test(BuyerNeeds::class)
            ->call('reopen', $need);

        $this->assertSame('ouvert', $need->fresh()->status->value);
    }

    public function test_a_learner_cannot_access_the_screen(): void
    {
        $learner = User::factory()->create();

        $this->actingAs($learner)->get(route('admin.buyer-needs'))->assertForbidden();
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.buyer-needs'))->assertRedirect(route('login'));
    }
}

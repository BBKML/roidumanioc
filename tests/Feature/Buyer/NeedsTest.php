<?php

namespace Tests\Feature\Buyer;

use App\Livewire\Buyer\NeedForm;
use App\Livewire\Buyer\Needs;
use App\Models\BuyerNeed;
use App\Models\BuyerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NeedsTest extends TestCase
{
    use RefreshDatabase;

    protected function makeBuyer(): User
    {
        $user = User::factory()->create();
        BuyerProfile::create([
            'user_id' => $user->id, 'buyer_type' => 'transformateur', 'zone' => 'Abidjan, Cocody',
        ]);

        return $user;
    }

    public function test_buyer_publishes_a_need_and_is_redirected_to_the_edit_page(): void
    {
        $user = $this->makeBuyer();

        Livewire::actingAs($user)->test(NeedForm::class)
            ->set('product_wanted', 'Manioc frais')
            ->set('quantity', '50')
            ->set('unit', 'kg')
            ->set('location', 'Abidjan')
            ->set('frequency', 'recurrent')
            ->set('budget_indicative', 5000)
            ->set('status', 'ouvert')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $need = BuyerNeed::firstOrFail();
        $this->assertSame('Manioc frais', $need->product_wanted);
        $this->assertSame('kg', $need->unit->value);
        $this->assertSame('recurrent', $need->frequency);
        $this->assertSame('ouvert', $need->status->value);
        $this->assertSame($user->buyerProfile->id, $need->buyer_profile_id);
    }

    public function test_quantity_must_be_positive_and_frequency_must_be_a_known_value(): void
    {
        $user = $this->makeBuyer();

        Livewire::actingAs($user)->test(NeedForm::class)
            ->set('product_wanted', 'Manioc frais')
            ->set('quantity', '0')
            ->set('location', 'Abidjan')
            ->call('save')
            ->assertHasErrors(['quantity']);

        Livewire::actingAs($user)->test(NeedForm::class)
            ->set('product_wanted', 'Manioc frais')
            ->set('quantity', '10')
            ->set('location', 'Abidjan')
            ->set('frequency', 'chaque-jour')
            ->call('save')
            ->assertHasErrors(['frequency']);
    }

    public function test_budget_indicative_must_be_a_positive_integer_or_null(): void
    {
        $user = $this->makeBuyer();

        Livewire::actingAs($user)->test(NeedForm::class)
            ->set('product_wanted', 'Manioc frais')
            ->set('quantity', '10')
            ->set('location', 'Abidjan')
            ->set('budget_indicative', -5)
            ->call('save')
            ->assertHasErrors(['budget_indicative']);

        Livewire::actingAs($user)->test(NeedForm::class)
            ->set('product_wanted', 'Manioc frais')
            ->set('quantity', '10')
            ->set('location', 'Abidjan')
            ->set('budget_indicative', null)
            ->call('save')
            ->assertHasNoErrors();
    }

    public function test_buyer_edits_their_need(): void
    {
        $user = $this->makeBuyer();
        $need = BuyerNeed::create([
            'buyer_profile_id' => $user->buyerProfile->id,
            'product_wanted' => 'Manioc frais', 'quantity' => 10, 'unit' => 'kg',
            'location' => 'Abidjan', 'frequency' => 'ponctuel', 'status' => 'ouvert',
        ]);

        Livewire::actingAs($user)->test(NeedForm::class, ['need' => $need])
            ->assertSet('product_wanted', 'Manioc frais')
            ->set('product_wanted', 'Manioc frais premium')
            ->set('status', 'satisfait')
            ->call('save')
            ->assertHasNoErrors();

        $need->refresh();
        $this->assertSame('Manioc frais premium', $need->product_wanted);
        $this->assertSame('satisfait', $need->status->value);
    }

    public function test_a_buyer_cannot_edit_another_buyers_need(): void
    {
        $owner = $this->makeBuyer();
        $intruder = $this->makeBuyer();
        $need = BuyerNeed::create([
            'buyer_profile_id' => $owner->buyerProfile->id,
            'product_wanted' => 'Manioc du propriétaire', 'quantity' => 10, 'unit' => 'kg',
            'location' => 'Abidjan', 'frequency' => 'ponctuel', 'status' => 'ouvert',
        ]);

        Livewire::actingAs($intruder)->test(NeedForm::class, ['need' => $need])
            ->assertForbidden();
    }

    public function test_buyer_only_sees_their_own_needs_in_the_list(): void
    {
        $owner = $this->makeBuyer();
        $other = $this->makeBuyer();
        BuyerNeed::create([
            'buyer_profile_id' => $owner->buyerProfile->id,
            'product_wanted' => 'Besoin du propriétaire', 'quantity' => 10, 'unit' => 'kg',
            'location' => 'Abidjan', 'frequency' => 'ponctuel', 'status' => 'ouvert',
        ]);
        BuyerNeed::create([
            'buyer_profile_id' => $other->buyerProfile->id,
            'product_wanted' => 'Besoin concurrent', 'quantity' => 5, 'unit' => 'sac',
            'location' => 'Bouaké', 'frequency' => 'ponctuel', 'status' => 'ouvert',
        ]);

        Livewire::actingAs($owner)->test(Needs::class)
            ->assertSee('Besoin du propriétaire')
            ->assertDontSee('Besoin concurrent');
    }

    public function test_a_buyer_cannot_delete_another_buyers_need(): void
    {
        $owner = $this->makeBuyer();
        $intruder = $this->makeBuyer();
        $need = BuyerNeed::create([
            'buyer_profile_id' => $owner->buyerProfile->id,
            'product_wanted' => 'Manioc frais', 'quantity' => 10, 'unit' => 'kg',
            'location' => 'Abidjan', 'frequency' => 'ponctuel', 'status' => 'ouvert',
        ]);

        Livewire::actingAs($intruder)->test(Needs::class)
            ->call('delete', $need->id)
            ->assertForbidden();

        $this->assertNotSoftDeleted($need);
    }

    public function test_buyer_deletes_their_need(): void
    {
        $user = $this->makeBuyer();
        $need = BuyerNeed::create([
            'buyer_profile_id' => $user->buyerProfile->id,
            'product_wanted' => 'Manioc frais', 'quantity' => 10, 'unit' => 'kg',
            'location' => 'Abidjan', 'frequency' => 'ponctuel', 'status' => 'ouvert',
        ]);

        Livewire::actingAs($user)->test(Needs::class)
            ->call('delete', $need->id)
            ->assertHasNoErrors();

        $this->assertSoftDeleted($need);
    }

    public function test_a_learner_without_a_buyer_profile_is_redirected_from_the_needs_pages(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('learner.buyer.needs'))
            ->assertRedirect(route('learner.buyer'));

        $this->actingAs($user)->get(route('learner.buyer.needs.create'))
            ->assertRedirect(route('learner.buyer'));
    }

    /**
     * Audit UX (Phase 2) : cette redirection ne doit plus être silencieuse — un message
     * explique pourquoi l'utilisateur atterrit sur l'activation du profil acheteur.
     */
    public function test_the_redirect_away_from_needs_explains_why(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('learner.buyer.needs'));

        $response->assertRedirect(route('learner.buyer'));
        $this->assertSame(
            "Activez d'abord votre profil acheteur pour accéder à cette page.",
            $response->getSession()->get('flash')
        );
    }

    public function test_needs_nav_link_appears_once_buyer_profile_exists(): void
    {
        $user = $this->makeBuyer();

        $this->actingAs($user)->get(route('learner.dashboard'))
            ->assertOk()
            ->assertSee('Mes besoins')
            ->assertSee('Profil acheteur');
    }

    public function test_expire_outdated_needs_command_expires_only_open_needs_past_their_wanted_date(): void
    {
        $user = $this->makeBuyer();

        $outdatedOpen = BuyerNeed::create([
            'buyer_profile_id' => $user->buyerProfile->id,
            'product_wanted' => 'Passé', 'quantity' => 10, 'unit' => 'kg',
            'location' => 'Abidjan', 'frequency' => 'ponctuel', 'status' => 'ouvert',
            'wanted_date' => now()->subDays(2)->toDateString(),
        ]);
        $futureOpen = BuyerNeed::create([
            'buyer_profile_id' => $user->buyerProfile->id,
            'product_wanted' => 'Futur', 'quantity' => 10, 'unit' => 'kg',
            'location' => 'Abidjan', 'frequency' => 'ponctuel', 'status' => 'ouvert',
            'wanted_date' => now()->addDays(2)->toDateString(),
        ]);
        $outdatedSatisfied = BuyerNeed::create([
            'buyer_profile_id' => $user->buyerProfile->id,
            'product_wanted' => 'Déjà satisfait', 'quantity' => 10, 'unit' => 'kg',
            'location' => 'Abidjan', 'frequency' => 'ponctuel', 'status' => 'satisfait',
            'wanted_date' => now()->subDays(2)->toDateString(),
        ]);

        $this->artisan('needs:expire-outdated')->assertSuccessful();

        $this->assertSame('expire', $outdatedOpen->fresh()->status->value);
        $this->assertSame('ouvert', $futureOpen->fresh()->status->value);
        $this->assertSame('satisfait', $outdatedSatisfied->fresh()->status->value);
    }

    public function test_saving_a_need_is_rate_limited(): void
    {
        $user = $this->makeBuyer();

        for ($i = 0; $i < 6; $i++) {
            Livewire::actingAs($user)->test(NeedForm::class)
                ->set('product_wanted', "Manioc frais $i")
                ->set('quantity', '50')
                ->set('unit', 'kg')
                ->set('location', 'Abidjan')
                ->set('frequency', 'ponctuel')
                ->set('status', 'ouvert')
                ->call('save')
                ->assertHasNoErrors();
        }

        Livewire::actingAs($user)->test(NeedForm::class)
            ->set('product_wanted', 'Une de trop')
            ->set('quantity', '50')
            ->set('unit', 'kg')
            ->set('location', 'Abidjan')
            ->set('frequency', 'ponctuel')
            ->set('status', 'ouvert')
            ->call('save')
            ->assertHasErrors('product_wanted');

        $this->assertSame(6, BuyerNeed::count());
    }
}

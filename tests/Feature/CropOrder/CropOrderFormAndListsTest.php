<?php

namespace Tests\Feature\CropOrder;

use App\Actions\CreateCropOrder;
use App\Livewire\Buyer\CropOrderForm;
use App\Livewire\Learner\Orders as LearnerOrders;
use App\Models\BuyerProfile;
use App\Models\CropOffer;
use App\Models\ProducerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CropOrderFormAndListsTest extends TestCase
{
    use RefreshDatabase;

    protected User $producerUser;

    protected ProducerProfile $producerProfile;

    protected CropOffer $offer;

    protected User $buyerUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->producerUser = User::factory()->create();
        $this->producerProfile = ProducerProfile::create([
            'user_id' => $this->producerUser->id, 'business_name' => 'Ferme Kouassi',
            'zone' => 'Daloa', 'activity_type' => 'recolte',
        ]);
        $this->offer = CropOffer::create([
            'producer_profile_id' => $this->producerProfile->id, 'product_name' => 'Manioc frais',
            'quantity' => 100, 'unit' => 'kg', 'location' => 'Daloa',
            'status' => 'publiee', 'is_available' => true,
        ]);

        $this->buyerUser = User::factory()->create();
        BuyerProfile::create(['user_id' => $this->buyerUser->id, 'buyer_type' => 'transformateur', 'zone' => 'Abidjan']);
    }

    public function test_a_buyer_can_submit_the_order_form(): void
    {
        Livewire::actingAs($this->buyerUser)->test(CropOrderForm::class, ['offer' => $this->offer])
            ->set('quantity', 15)
            ->set('unit', 'kg')
            ->set('deliveryLocation', 'Cocody, Abidjan')
            ->set('paymentMethod', 'Espèces à la livraison')
            ->call('send')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('crop_orders', [
            'crop_offer_id' => $this->offer->id,
            'delivery_location' => 'Cocody, Abidjan',
            'payment_method' => 'Espèces à la livraison',
            'status' => 'en_attente_producteur',
        ]);
    }

    public function test_the_other_payment_method_requires_free_text(): void
    {
        Livewire::actingAs($this->buyerUser)->test(CropOrderForm::class, ['offer' => $this->offer])
            ->set('quantity', 15)
            ->set('unit', 'kg')
            ->set('deliveryLocation', 'Cocody, Abidjan')
            ->set('paymentMethod', 'Autre')
            ->call('send')
            ->assertHasErrors(['paymentMethodOther' => 'required_if']);
    }

    public function test_a_producer_cannot_open_the_form_for_their_own_offer(): void
    {
        Livewire::actingAs($this->producerUser)->test(CropOrderForm::class, ['offer' => $this->offer])
            ->assertForbidden();
    }

    /**
     * Les listes producteur/acheteur des commandes structurées vivent désormais comme
     * sections de la page unique « Mes commandes » (App\Livewire\Learner\Orders) — un seul
     * onglet « commande » par compte, plus de composants Producer\CropOrders/Buyer\CropOrders
     * séparés (fusion demandée en usage réel, même principe que les Phases 15/16).
     */
    public function test_producer_section_filters_new_orders_by_default(): void
    {
        app(CreateCropOrder::class)->handle(
            $this->buyerUser, $this->offer, 10.0, 'kg', 'Cocody', null, null, 'Mobile Money', null, null,
        );

        Livewire::actingAs($this->producerUser)->test(LearnerOrders::class)
            ->assertOk()
            ->assertSee('Commandes reçues (producteur)')
            ->assertSee('Manioc frais');
    }

    public function test_buyer_only_sees_their_own_orders_in_the_merged_page(): void
    {
        app(CreateCropOrder::class)->handle(
            $this->buyerUser, $this->offer, 10.0, 'kg', 'Cocody', null, null, 'Mobile Money', null, null,
        );

        $otherBuyer = User::factory()->create();
        BuyerProfile::create(['user_id' => $otherBuyer->id, 'buyer_type' => 'commercant', 'zone' => 'Bouaké']);

        Livewire::actingAs($otherBuyer)->test(LearnerOrders::class)
            ->assertOk()
            ->assertDontSee('Manioc frais');

        Livewire::actingAs($this->buyerUser)->test(LearnerOrders::class)
            ->assertOk()
            ->assertSee('Commandes producteurs (acheteur)')
            ->assertSee('Manioc frais');
    }

    public function test_a_plain_learner_does_not_see_either_crop_order_section(): void
    {
        $plainUser = User::factory()->create();

        Livewire::actingAs($plainUser)->test(LearnerOrders::class)
            ->assertOk()
            ->assertDontSee('Commandes reçues (producteur)')
            ->assertDontSee('Commandes producteurs (acheteur)');
    }
}

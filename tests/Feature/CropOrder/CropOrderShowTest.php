<?php

namespace Tests\Feature\CropOrder;

use App\Actions\CreateCropOrder;
use App\Enums\DeliveryAssistStatus;
use App\Livewire\CropOrder\Show;
use App\Models\BuyerProfile;
use App\Models\CropOffer;
use App\Models\CropOrder;
use App\Models\ProducerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CropOrderShowTest extends TestCase
{
    use RefreshDatabase;

    protected User $producerUser;

    protected ProducerProfile $producerProfile;

    protected CropOffer $offer;

    protected User $buyerUser;

    protected BuyerProfile $buyerProfile;

    protected User $stranger;

    protected User $admin;

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
        $this->buyerProfile = BuyerProfile::create([
            'user_id' => $this->buyerUser->id, 'buyer_type' => 'transformateur', 'zone' => 'Abidjan',
        ]);

        $this->stranger = User::factory()->create();
        $this->admin = User::factory()->create(['role' => 'admin', 'status' => 'actif']);
    }

    protected function makeOrder(): CropOrder
    {
        return app(CreateCropOrder::class)->handle(
            $this->buyerUser, $this->offer, 20.0, 'kg', 'Cocody', null, null, 'Mobile Money', null, null,
        );
    }

    public function test_a_third_party_cannot_view_the_order(): void
    {
        $order = $this->makeOrder();

        Livewire::actingAs($this->stranger)->test(Show::class, ['cropOrder' => $order])->assertForbidden();
    }

    public function test_an_admin_can_view_the_order_read_only(): void
    {
        $order = $this->makeOrder();

        Livewire::actingAs($this->admin)->test(Show::class, ['cropOrder' => $order])
            ->assertOk()
            ->assertViewHas('isViewerAdmin', true);
    }

    public function test_the_full_flow_through_the_livewire_component(): void
    {
        $order = $this->makeOrder();

        Livewire::actingAs($this->producerUser)->test(Show::class, ['cropOrder' => $order])
            ->call('accept')
            ->assertHasNoErrors();
        $order->refresh();
        $this->assertSame('acceptee', $order->status->value);

        Livewire::actingAs($this->producerUser)->test(Show::class, ['cropOrder' => $order])
            ->set('productPriceTotal', '300000')
            ->set('deliveryFeeProposed', '25000')
            ->call('submitDeliveryConditions')
            ->assertHasNoErrors();
        $order->refresh();
        $this->assertSame('en_attente_validation_admin', $order->status->value);

        // L'acheteur ne voit rien tant que l'admin n'a pas validé.
        Livewire::actingAs($this->buyerUser)->test(Show::class, ['cropOrder' => $order])
            ->assertDontSee('300 000')
            ->assertDontSee('25 000');

        Livewire::actingAs($this->admin)->test(Show::class, ['cropOrder' => $order])
            ->assertSee('300 000')
            ->assertSee('25 000')
            ->call('approveDeliveryConditions')
            ->assertHasNoErrors();
        $order->refresh();
        $this->assertSame('negociation_livraison', $order->status->value);

        // Le montant proposé apparaît dans l'historique — jamais un bouton "Aide livraison" pour l'instant.
        Livewire::actingAs($this->buyerUser)->test(Show::class, ['cropOrder' => $order])
            ->assertDontSee('🚚 Aide livraison')
            ->set('proposedAmount', '15000')
            ->call('proposeDeliveryFee')
            ->assertHasNoErrors();
        $order->refresh();
        $this->assertCount(2, $order->deliveryProposals);

        Livewire::actingAs($this->producerUser)->test(Show::class, ['cropOrder' => $order])
            ->call('acceptDeliveryFee')
            ->assertHasNoErrors();
        $order->refresh();
        $this->assertSame('commande_confirmee', $order->status->value);
        $this->assertSame(315000, $order->total_amount);

        // Les 3 choix n'apparaissent QU'à partir d'ici.
        Livewire::actingAs($this->buyerUser)->test(Show::class, ['cropOrder' => $order])
            ->assertSee('🚚 Aide livraison')
            ->call('requestDeliveryAssistance')
            ->assertHasNoErrors();
        $order->refresh();
        $this->assertSame('aide_livraison', $order->status->value);
        $this->assertNotNull($order->deliveryAssist);
    }

    public function test_admin_can_reject_conditions_and_producer_can_resubmit(): void
    {
        $order = $this->makeOrder();
        $order->accept($this->producerUser);
        $order->fresh()->submitDeliveryConditionsForReview($this->producerUser, 300000, 25000);
        $order->refresh();

        Livewire::actingAs($this->admin)->test(Show::class, ['cropOrder' => $order])
            ->set('adminReviewNote', 'Frais trop élevés')
            ->call('rejectDeliveryConditions')
            ->assertHasNoErrors();
        $order->refresh();
        $this->assertSame('acceptee', $order->status->value);

        Livewire::actingAs($this->producerUser)->test(Show::class, ['cropOrder' => $order])
            ->set('productPriceTotal', '280000')
            ->set('deliveryFeeProposed', '20000')
            ->call('submitDeliveryConditions')
            ->assertHasNoErrors();
        $order->refresh();
        $this->assertSame('en_attente_validation_admin', $order->status->value);
    }

    public function test_a_party_cannot_approve_or_reject_conditions(): void
    {
        $order = $this->makeOrder();
        $order->accept($this->producerUser);
        $order->fresh()->submitDeliveryConditionsForReview($this->producerUser, 300000, 25000);
        $order->refresh();

        Livewire::actingAs($this->producerUser)->test(Show::class, ['cropOrder' => $order])
            ->call('approveDeliveryConditions')
            ->assertForbidden();

        Livewire::actingAs($this->buyerUser)->test(Show::class, ['cropOrder' => $order])
            ->call('approveDeliveryConditions')
            ->assertForbidden();
    }

    protected function confirmedOrder(): CropOrder
    {
        $order = $this->makeOrder();
        $order->accept($this->producerUser);
        $order->fresh()->submitDeliveryConditionsForReview($this->producerUser, 300000, 25000);
        $order->fresh()->approveDeliveryConditions($this->admin);
        $order = $order->fresh();
        $order->proposeDeliveryFee($this->buyerUser, 15000);
        $order = $order->fresh();
        $order->acceptDeliveryFee($this->producerUser);

        return $order->fresh();
    }

    public function test_self_arranged_delivery_flow_through_the_livewire_component(): void
    {
        $order = $this->confirmedOrder();

        Livewire::actingAs($this->buyerUser)->test(Show::class, ['cropOrder' => $order])
            ->call('declareSelfArrangedDelivery', 'acheteur')
            ->assertHasNoErrors();
        $order->refresh();
        $this->assertSame('livraison_auto_organisee', $order->status->value);
        $this->assertSame('acheteur', $order->self_arranged_mode);

        // Seul l'acheteur peut confirmer la réception.
        Livewire::actingAs($this->producerUser)->test(Show::class, ['cropOrder' => $order])
            ->call('confirmSelfArrangedDelivery')
            ->assertForbidden();

        Livewire::actingAs($this->buyerUser)->test(Show::class, ['cropOrder' => $order])
            ->call('confirmSelfArrangedDelivery')
            ->assertHasNoErrors();
        $order->refresh();
        $this->assertSame('livree', $order->status->value);
    }

    public function test_the_refuse_button_is_not_shown_once_accepted(): void
    {
        $order = $this->makeOrder();
        $order->accept($this->producerUser);

        Livewire::actingAs($this->producerUser)->test(Show::class, ['cropOrder' => $order])
            ->assertViewHas('canRefuse', false);
    }
}

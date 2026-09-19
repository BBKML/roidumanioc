<?php

namespace Tests\Feature\Admin;

use App\Actions\CreateCropOrder;
use App\Enums\DeliveryAssistStatus;
use App\Livewire\Admin\DeliveryAssists;
use App\Models\BuyerProfile;
use App\Models\CropOffer;
use App\Models\CropOrder;
use App\Models\ProducerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminDeliveryAssistsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $producerUser;

    protected User $buyerUser;

    protected CropOffer $offer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();

        $this->producerUser = User::factory()->create();
        $producerProfile = ProducerProfile::create([
            'user_id' => $this->producerUser->id, 'business_name' => 'Ferme Kouassi',
            'zone' => 'Daloa', 'activity_type' => 'recolte',
        ]);
        $this->offer = CropOffer::create([
            'producer_profile_id' => $producerProfile->id, 'product_name' => 'Manioc frais',
            'quantity' => 100, 'unit' => 'kg', 'location' => 'Daloa',
            'status' => 'publiee', 'is_available' => true,
        ]);

        $this->buyerUser = User::factory()->create();
        BuyerProfile::create(['user_id' => $this->buyerUser->id, 'buyer_type' => 'transformateur', 'zone' => 'Abidjan']);
    }

    protected function confirmedOrder(): CropOrder
    {
        $order = app(CreateCropOrder::class)->handle(
            $this->buyerUser, $this->offer, 10.0, 'kg', 'Cocody', null, null, 'Mobile Money', null, null,
        );
        $order->accept($this->producerUser);
        $order->fresh()->submitDeliveryConditionsForReview($this->producerUser, 100000, 10000);
        $order->fresh()->approveDeliveryConditions($this->admin);
        $order = $order->fresh();
        $order->acceptDeliveryFee($this->buyerUser);

        return $order->fresh();
    }

    public function test_a_non_admin_cannot_access_the_screen(): void
    {
        $this->actingAs($this->buyerUser)->get(route('admin.delivery-assists'))->assertForbidden();
    }

    public function test_the_screen_shows_all_the_required_columns(): void
    {
        $order = $this->confirmedOrder();
        $order->requestDeliveryAssistance($this->buyerUser);
        $order->refresh();

        Livewire::actingAs($this->admin)->test(DeliveryAssists::class)
            ->assertOk()
            ->assertSee('Manioc frais')
            ->assertSee('Ferme Kouassi')
            ->assertSee('Cocody')
            ->assertSee('Mobile Money')
            ->assertSee('110 000 FCFA');
    }

    public function test_the_admin_can_progress_the_delivery_status(): void
    {
        $order = $this->confirmedOrder();
        $order->requestDeliveryAssistance($this->buyerUser);
        $order->refresh();

        Livewire::actingAs($this->admin)->test(DeliveryAssists::class)
            ->call('markStep', $order->id, 'en_preparation')
            ->assertHasNoErrors();

        $this->assertSame(DeliveryAssistStatus::EnPreparation, $order->fresh()->deliveryAssist->status);
    }
}

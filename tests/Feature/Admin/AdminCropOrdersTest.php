<?php

namespace Tests\Feature\Admin;

use App\Actions\CreateCropOrder;
use App\Livewire\Admin\CropOrders;
use App\Models\BuyerProfile;
use App\Models\CropOffer;
use App\Models\ProducerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminCropOrdersTest extends TestCase
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

    public function test_a_non_admin_cannot_access_the_screen(): void
    {
        $this->actingAs($this->buyerUser)->get(route('admin.crop-orders'))->assertForbidden();
    }

    public function test_the_admin_sees_the_list_and_can_filter_by_status(): void
    {
        $order = app(CreateCropOrder::class)->handle(
            $this->buyerUser, $this->offer, 10.0, 'kg', 'Cocody', null, null, 'Mobile Money', null, null,
        );

        Livewire::actingAs($this->admin)->test(CropOrders::class)
            ->assertOk()
            ->assertSee('Manioc frais')
            ->set('filter', 'en_attente_producteur')
            ->assertSee('Manioc frais')
            ->set('filter', 'livree')
            ->assertDontSee('Manioc frais');
    }
}

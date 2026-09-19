<?php

namespace Tests\Feature\CropOrder;

use App\Actions\CreateCropOrder;
use App\Enums\CropOrderStatus;
use App\Models\BuyerProfile;
use App\Models\CropOffer;
use App\Models\CropOrder;
use App\Models\ProducerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CreateCropOrderTest extends TestCase
{
    use RefreshDatabase;

    protected User $producerUser;

    protected ProducerProfile $producerProfile;

    protected CropOffer $offer;

    protected User $buyerUser;

    protected BuyerProfile $buyerProfile;

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
    }

    protected function placeOrder(): CropOrder
    {
        return app(CreateCropOrder::class)->handle(
            $this->buyerUser,
            $this->offer,
            10.0,
            'kg',
            'Cocody, Abidjan',
            null,
            null,
            'Mobile Money',
            null,
            'Bonjour, je souhaite commander.',
        );
    }

    public function test_a_buyer_can_place_an_order_and_it_starts_pending_producer(): void
    {
        $order = $this->placeOrder();

        $this->assertSame(CropOrderStatus::EnAttenteProducteur, $order->status);
        $this->assertSame($this->producerProfile->id, $order->producer_profile_id);
        $this->assertSame($this->buyerProfile->id, $order->buyer_profile_id);
        $this->assertSame('Manioc frais', $order->product_name);
        $this->assertSame(10.0, (float) $order->requested_quantity);
    }

    public function test_a_duplicate_open_order_on_the_same_offer_is_rejected(): void
    {
        $this->placeOrder();

        $this->expectException(ValidationException::class);
        $this->placeOrder();
    }

    public function test_a_new_order_is_allowed_after_the_previous_one_was_refused(): void
    {
        $first = $this->placeOrder();
        $first->refuse($this->producerUser);

        $second = $this->placeOrder();

        $this->assertNotSame($first->id, $second->id);
    }

    public function test_a_producer_cannot_order_their_own_offer(): void
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        app(CreateCropOrder::class)->handle(
            $this->producerUser,
            $this->offer,
            5.0,
            'kg',
            'Daloa',
            null,
            null,
            'Espèces à la livraison',
            null,
            null,
        );
    }

    public function test_an_unpublished_offer_cannot_be_ordered(): void
    {
        $this->offer->update(['status' => 'brouillon']);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->placeOrder();
    }

    public function test_a_buyer_without_a_profile_cannot_order(): void
    {
        $noProfileBuyer = User::factory()->create();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        app(CreateCropOrder::class)->handle(
            $noProfileBuyer,
            $this->offer,
            5.0,
            'kg',
            'Daloa',
            null,
            null,
            'Espèces à la livraison',
            null,
            null,
        );
    }

    public function test_ordering_is_rate_limited(): void
    {
        RateLimiter::clear('crop-order:'.$this->buyerUser->id);

        for ($i = 0; $i < 10; $i++) {
            RateLimiter::hit('crop-order:'.$this->buyerUser->id, 3600);
        }

        $this->expectException(ValidationException::class);
        $this->placeOrder();
    }
}

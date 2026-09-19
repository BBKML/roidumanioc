<?php

namespace Tests\Feature\Learner;

use App\Livewire\Admin\Orders as AdminOrders;
use App\Livewire\Learner\ShopCheckout;
use App\Mail\NewOrderMail;
use App\Mail\OrderUpdateMail;
use App\Models\Order;
use App\Models\PaymentSetting;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProductOrderTest extends TestCase
{
    use RefreshDatabase;

    protected User $learner;

    protected ShopProduct $product;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Storage::fake('local');
        Mail::fake();

        PaymentSetting::current()->update(['whatsapp' => '2250700000000', 'wave' => '0707', 'delivery_fee' => 2000]);
        $this->learner = User::factory()->create(['role' => 'apprenant', 'status' => 'actif', 'phone' => '0700']);
        $this->product = ShopProduct::create([
            'name' => 'Engrais organique', 'category' => 'Engrais', 'price' => 12000, 'stock' => 10, 'is_active' => true,
        ]);
    }

    private function checkout()
    {
        return Livewire::actingAs($this->learner->fresh())
            ->test(ShopCheckout::class, ['product' => $this->product->fresh()]);
    }

    public function test_pay_on_delivery_creates_an_order_without_payment_and_reserves_stock(): void
    {
        $this->checkout()
            ->set('mode', 'on_delivery')
            ->set('quantity', 3)
            ->set('contactPhone', '0700000000')
            ->set('deliveryAddress', 'Quartier X')
            ->set('deliveryCity', 'Daloa')
            ->call('placeOrder')
            ->assertRedirect(route('learner.orders'));

        $order = Order::firstOrFail();
        $this->assertSame('on_delivery', $order->payment_mode->value);
        $this->assertSame('validee', $order->status->value);        // à préparer
        $this->assertSame(36000, $order->amount);                    // 12000 × 3
        $this->assertSame(2000, $order->delivery_fee);
        $this->assertSame(38000, $order->total());
        $this->assertSame('Daloa', $order->delivery_city);
        $this->assertStringStartsWith('CMD-', $order->reference);

        $this->assertDatabaseCount('payments', 0);
        $this->assertSame(7, $this->product->fresh()->stock);        // 10 − 3 réservés

        Mail::assertSent(NewOrderMail::class);
        Mail::assertSent(OrderUpdateMail::class);
    }

    public function test_pay_online_creates_an_order_and_a_pending_payment_for_the_full_total(): void
    {
        $this->checkout()
            ->set('mode', 'online')
            ->set('quantity', 2)
            ->set('contactPhone', '0700000000')
            ->set('deliveryAddress', 'Rue Y')
            ->set('deliveryCity', 'Abidjan')
            ->set('method', 'wave')
            ->set('declaredAmount', 26000)
            ->set('proof', UploadedFile::fake()->image('recu.jpg'))
            ->call('placeOrder')
            ->assertRedirect(route('learner.orders'));

        $order = Order::firstOrFail();
        $this->assertSame('online', $order->payment_mode->value);
        $this->assertSame('paiement', $order->status->value);

        $payment = $order->payment;
        $this->assertNotNull($payment);
        $this->assertSame('a_verifier', $payment->status->value);
        $this->assertSame(26000, $payment->amount);  // (12000 × 2) + 2000 livraison
    }

    public function test_cannot_order_more_than_the_stock(): void
    {
        $this->checkout()
            ->set('mode', 'on_delivery')
            ->set('quantity', 99)
            ->set('contactPhone', '07')
            ->set('deliveryAddress', 'X')
            ->set('deliveryCity', 'Y')
            ->call('placeOrder')
            ->assertHasErrors('quantity');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_admin_ships_then_delivers_an_order_and_the_client_is_emailed(): void
    {
        $admin = User::factory()->admin()->create();
        $order = $this->learner->orders()->create([
            'customer_name' => $this->learner->name, 'contact_phone' => '07',
            'delivery_address' => 'X', 'delivery_city' => 'Daloa',
            'orderable_type' => $this->product->getMorphClass(), 'orderable_id' => $this->product->id,
            'item_label' => 'Engrais ×1', 'quantity' => 1, 'amount' => 12000, 'delivery_fee' => 2000,
            'payment_mode' => 'on_delivery', 'status' => 'validee',
        ]);

        Livewire::actingAs($admin)->test(AdminOrders::class)
            ->call('ship', $order->id)
            ->call('deliver', $order->id);

        $order->refresh();
        $this->assertSame('livree', $order->status->value);
        $this->assertSame($admin->id, $order->handled_by);
        $this->assertNotNull($order->delivered_at);
        Mail::assertSent(OrderUpdateMail::class);
    }

    public function test_cancelling_an_order_restores_the_stock(): void
    {
        $admin = User::factory()->admin()->create();
        $this->product->update(['stock' => 10]);

        $order = $this->learner->orders()->create([
            'customer_name' => $this->learner->name, 'contact_phone' => '07',
            'delivery_address' => 'X', 'delivery_city' => 'Y',
            'orderable_type' => $this->product->getMorphClass(), 'orderable_id' => $this->product->id,
            'item_label' => 'Engrais ×4', 'quantity' => 4, 'amount' => 48000,
            'payment_mode' => 'on_delivery', 'status' => 'validee',
        ]); // created event -> stock 6

        $this->assertSame(6, $this->product->fresh()->stock);

        Livewire::actingAs($admin)->test(AdminOrders::class)->call('cancel', $order->id);

        $this->assertSame('refuse', $order->fresh()->status->value);
        $this->assertSame(10, $this->product->fresh()->stock); // réintégré
    }

    public function test_no_show_cancellation_adds_a_strike_and_blocks_cod_after_two(): void
    {
        $admin = User::factory()->admin()->create();

        foreach (range(1, 2) as $i) {
            $order = $this->learner->orders()->create([
                'customer_name' => $this->learner->name, 'contact_phone' => '07',
                'delivery_address' => 'X', 'delivery_city' => 'Y',
                'orderable_type' => $this->product->getMorphClass(), 'orderable_id' => $this->product->id,
                'item_label' => 'Engrais ×1', 'quantity' => 1, 'amount' => 12000,
                'payment_mode' => 'on_delivery', 'status' => 'expediee',
            ]);
            Livewire::actingAs($admin)->test(AdminOrders::class)->call('cancel', $order->id, 'no_show');
        }

        $this->assertSame(2, $this->learner->fresh()->delivery_strikes);
        $this->assertFalse($this->learner->fresh()->canPayOnDelivery());

        // Le checkout ne propose plus que le paiement en ligne.
        $this->checkout()->assertSet('codAllowed', false)->assertSet('mode', 'online');

        // Et refuse un placement à la livraison forcé.
        $this->checkout()
            ->set('mode', 'on_delivery')->set('quantity', 1)
            ->set('contactPhone', '07')->set('deliveryAddress', 'X')->set('deliveryCity', 'Y')
            ->call('placeOrder')
            ->assertForbidden();
    }

    public function test_a_delivered_order_cannot_be_cancelled(): void
    {
        $this->product->update(['stock' => 10]);
        $order = $this->learner->orders()->create([
            'customer_name' => $this->learner->name, 'contact_phone' => '07',
            'delivery_address' => 'X', 'delivery_city' => 'Y',
            'orderable_type' => $this->product->getMorphClass(), 'orderable_id' => $this->product->id,
            'item_label' => 'Engrais ×1', 'quantity' => 1, 'amount' => 12000,
            'payment_mode' => 'on_delivery', 'status' => 'livree',
        ]);
        $stockAfterCreation = $this->product->fresh()->stock;

        $order->fresh()->cancel(User::factory()->admin()->create());

        $this->assertSame('livree', $order->fresh()->status->value);
        $this->assertSame($stockAfterCreation, $this->product->fresh()->stock); // pas réintégré
    }

    public function test_an_already_refused_order_cannot_be_cancelled_again(): void
    {
        $order = $this->learner->orders()->create([
            'customer_name' => $this->learner->name, 'contact_phone' => '07',
            'delivery_address' => 'X', 'delivery_city' => 'Y',
            'orderable_type' => $this->product->getMorphClass(), 'orderable_id' => $this->product->id,
            'item_label' => 'Engrais ×1', 'quantity' => 1, 'amount' => 12000,
            'payment_mode' => 'on_delivery', 'status' => 'refuse',
        ]);

        $order->fresh()->cancel(User::factory()->admin()->create());

        $this->assertSame('refuse', $order->fresh()->status->value);
        $this->assertNull($order->fresh()->handled_by);
    }

    public function test_mark_validated_only_transitions_from_paiement(): void
    {
        $order = $this->learner->orders()->create([
            'customer_name' => $this->learner->name, 'contact_phone' => '07',
            'delivery_address' => 'X', 'delivery_city' => 'Y',
            'orderable_type' => $this->product->getMorphClass(), 'orderable_id' => $this->product->id,
            'item_label' => 'Engrais ×1', 'quantity' => 1, 'amount' => 12000,
            'payment_mode' => 'online', 'status' => 'expediee',
        ]);

        $order->fresh()->markValidated();

        $this->assertSame('expediee', $order->fresh()->status->value);
    }

    public function test_open_cod_orders_are_capped_at_three(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->learner->orders()->create([
                'customer_name' => $this->learner->name, 'contact_phone' => '07',
                'delivery_address' => 'X', 'delivery_city' => 'Y',
                'orderable_type' => $this->product->getMorphClass(), 'orderable_id' => $this->product->id,
                'item_label' => 'x', 'quantity' => 1, 'amount' => 12000,
                'payment_mode' => 'on_delivery', 'status' => 'validee',
            ]);
        }

        $this->checkout()->assertSet('codAllowed', false);
    }
}

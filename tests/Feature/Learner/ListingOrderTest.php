<?php

namespace Tests\Feature\Learner;

use App\Livewire\Admin\Orders as AdminOrders;
use App\Livewire\Learner\ListingCheckout;
use App\Mail\ListingOrderMail;
use App\Mail\NewOrderMail;
use App\Mail\OrderUpdateMail;
use App\Models\MarketplaceListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class ListingOrderTest extends TestCase
{
    use RefreshDatabase;

    protected User $buyer;

    protected User $producer;

    protected MarketplaceListing $listing;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Mail::fake();

        $this->buyer = User::factory()->create(['role' => 'apprenant', 'status' => 'actif', 'phone' => '0700']);
        $this->producer = User::factory()->create(['role' => 'apprenant', 'status' => 'actif', 'email' => 'prod@example.ci']);
        $this->listing = MarketplaceListing::create([
            'user_id' => $this->producer->id,
            'type' => 'Récolte', 'title' => '10 tonnes de manioc frais', 'location' => 'Daloa',
            'price_label' => '100 FCFA / Kg', 'seller_name' => $this->producer->name, 'status' => 'validee',
        ]);
    }

    public function test_ordering_a_listing_creates_a_traced_order_without_payment(): void
    {
        Livewire::actingAs($this->buyer)->test(ListingCheckout::class, ['listing' => $this->listing])
            ->set('quantityText', '500 kg')
            ->set('contactPhone', '0700000000')
            ->set('deliveryAddress', 'Rue X')
            ->set('deliveryCity', 'Yamoussoukro')
            ->set('message', 'Livraison souhaitée mercredi.')
            ->call('placeOrder')
            ->assertRedirect(route('learner.orders'));

        $order = $this->buyer->orders()->firstOrFail();
        $this->assertSame('direct', $order->payment_mode->value);
        $this->assertSame('validee', $order->status->value);
        $this->assertSame(0, $order->amount);
        $this->assertSame(MarketplaceListing::class, $order->orderable_type);
        $this->assertSame($this->listing->id, $order->orderable_id);
        $this->assertStringContainsString('500 kg', $order->item_label);

        $this->assertDatabaseCount('payments', 0);

        Mail::assertSent(OrderUpdateMail::class);            // acheteur
        Mail::assertSent(ListingOrderMail::class);           // producteur
        Mail::assertSent(NewOrderMail::class);               // admin
    }

    public function test_one_open_request_per_listing(): void
    {
        $make = fn () => Livewire::actingAs($this->buyer)->test(ListingCheckout::class, ['listing' => $this->listing])
            ->set('quantityText', '1 sac')->set('contactPhone', '07')
            ->set('deliveryAddress', 'X')->set('deliveryCity', 'Y')
            ->call('placeOrder');

        $make()->assertHasNoErrors();
        $make()->assertHasErrors('quantityText');

        $this->assertSame(1, $this->buyer->orders()->count());
    }

    public function test_listing_order_shows_in_the_admin_orders_screen_and_can_be_concluded(): void
    {
        $admin = User::factory()->admin()->create();

        $order = $this->buyer->orders()->create([
            'customer_name' => $this->buyer->name, 'contact_phone' => '07',
            'delivery_address' => 'X', 'delivery_city' => 'Y',
            'orderable_type' => MarketplaceListing::class, 'orderable_id' => $this->listing->id,
            'item_label' => 'Manioc — 500 kg', 'quantity' => 1, 'amount' => 0,
            'payment_mode' => 'direct', 'status' => 'validee',
        ]);

        Livewire::actingAs($admin)->test(AdminOrders::class)
            ->call('ship', $order->id)
            ->call('deliver', $order->id);

        $this->assertSame('livree', $order->fresh()->status->value);
    }

    public function test_marketplace_commander_link_points_to_the_listing_checkout(): void
    {
        $this->actingAs($this->buyer)->get(route('learner.marketplace'))
            ->assertOk()
            ->assertSee(route('learner.listing-checkout', $this->listing), false);
    }
}

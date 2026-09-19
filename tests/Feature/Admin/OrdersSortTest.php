<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Orders;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Audit architecture : tri par colonne ajouté sur ce tableau (aucun tableau du back-office
 * n'en avait). Sans "orderable" réel (ShopProduct/MarketplaceListing) : le réajustement de
 * stock lié à la création d'une commande (Order::booted()) ne s'applique qu'aux
 * ShopProduct, donc sans danger ici.
 */
class OrdersSortTest extends TestCase
{
    use RefreshDatabase;

    protected function makeOrder(string $item, int $amount): Order
    {
        return Order::create([
            'customer_name' => 'Client Test',
            'item_label' => $item,
            'amount' => $amount,
            'status' => 'validee',
        ]);
    }

    public function test_clicking_the_total_column_toggles_order_by_amount(): void
    {
        $admin = User::factory()->admin()->create();
        $this->makeOrder('Petit article', 5000);
        $this->makeOrder('Gros article', 50000);

        Livewire::actingAs($admin)->test(Orders::class)
            ->set('filter', 'tous')
            ->call('sortBy', 'amount')
            ->assertSet('sort', 'amount')
            ->assertSet('direction', 'asc')
            ->assertViewHas('orders', fn ($orders) => $orders->pluck('amount')->first() === 5000)
            ->call('sortBy', 'amount')
            ->assertSet('direction', 'desc')
            ->assertViewHas('orders', fn ($orders) => $orders->pluck('amount')->first() === 50000);
    }

    /** Sécurité : $sort vient de l'URL — une colonne non autorisée doit être ignorée. */
    public function test_sorting_by_an_unknown_column_is_ignored(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)->test(Orders::class)
            ->call('sortBy', 'user_id')
            ->assertSet('sort', 'ordered_at')
            ->assertSuccessful();
    }
}

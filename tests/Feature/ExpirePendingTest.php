<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\Formation;
use App\Models\Payment;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ExpirePendingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_abandoned_product_order_is_cancelled_and_stock_restored(): void
    {
        $user = User::factory()->create(['role' => 'apprenant']);
        $product = ShopProduct::create(['name' => 'Engrais', 'category' => 'E', 'price' => 12000, 'stock' => 10, 'is_active' => true]);

        $order = $user->orders()->create([
            'customer_name' => $user->name, 'contact_phone' => '07', 'delivery_address' => 'X', 'delivery_city' => 'Y',
            'orderable_type' => $product->getMorphClass(), 'orderable_id' => $product->id,
            'item_label' => 'Engrais ×2', 'quantity' => 2, 'amount' => 24000,
            'payment_mode' => 'online', 'status' => 'paiement',
            'ordered_at' => now()->subDays(6),
        ]);
        $this->assertSame(8, $product->fresh()->stock);

        $this->artisan('payments:expire-pending')->assertSuccessful();

        $this->assertSame('refuse', $order->fresh()->status->value);
        $this->assertSame(10, $product->fresh()->stock);
    }

    public function test_order_with_an_uploaded_proof_is_left_for_the_admin(): void
    {
        $user = User::factory()->create(['role' => 'apprenant']);
        $product = ShopProduct::create(['name' => 'P', 'category' => 'E', 'price' => 12000, 'stock' => 10, 'is_active' => true]);
        $order = $user->orders()->create([
            'customer_name' => $user->name, 'contact_phone' => '07', 'delivery_address' => 'X', 'delivery_city' => 'Y',
            'orderable_type' => $product->getMorphClass(), 'orderable_id' => $product->id,
            'item_label' => 'P ×1', 'quantity' => 1, 'amount' => 12000,
            'payment_mode' => 'online', 'status' => 'paiement', 'ordered_at' => now()->subDays(10),
        ]);
        Payment::create([
            'user_id' => $user->id, 'order_id' => $order->id,
            'payable_type' => $product->getMorphClass(), 'payable_id' => $product->id,
            'label' => 'P', 'amount' => 12000, 'method' => 'wave', 'declared_amount' => 12000,
            'proof_path' => 'proofs/x.jpg',
        ]);

        $this->artisan('payments:expire-pending');

        $this->assertSame('paiement', $order->fresh()->status->value); // intact
    }

    public function test_abandoned_pending_enrolment_is_expired(): void
    {
        $user = User::factory()->create(['role' => 'apprenant']);
        $formation = Formation::create(['title' => 'F', 'category' => 'C', 'price' => 15000, 'status' => 'publiee']);

        $enrolment = Enrollment::create([
            'user_id' => $user->id, 'formation_id' => $formation->id, 'status' => 'paiement',
        ]);
        DB::table('enrollments')->where('id', $enrolment->id)
            ->update(['created_at' => now()->subDays(8)]);

        $this->artisan('payments:expire-pending')->assertSuccessful();

        $this->assertSame('refuse', $enrolment->fresh()->status->value);
        $this->assertFalse($user->fresh()->can('follow', $formation));
    }
}

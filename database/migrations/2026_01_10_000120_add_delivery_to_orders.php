<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Commandes de produits : mode de paiement (en ligne / à la livraison),
 * coordonnées de livraison, frais, référence.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('reference', 16)->nullable()->after('id')->index();
            $table->string('payment_mode', 16)->default('online')->after('status'); // online | on_delivery
            $table->string('contact_phone', 40)->nullable()->after('customer_name');
            $table->string('delivery_address')->nullable()->after('contact_phone');
            $table->string('delivery_city', 120)->nullable()->after('delivery_address');
            $table->unsignedInteger('delivery_fee')->default(0)->after('amount');
            $table->text('customer_note')->nullable()->after('delivery_fee');
            $table->foreignId('handled_by')->nullable()->after('customer_note')->constrained('users')->nullOnDelete();
            $table->timestamp('delivered_at')->nullable()->after('ordered_at');
        });

        // Rétro-compat : référence + mode pour les commandes existantes.
        DB::table('orders')->whereNull('reference')->orderBy('id')->each(function ($row) {
            DB::table('orders')->where('id', $row->id)->update([
                'reference' => 'CMD-'.str_pad((string) $row->id, 4, '0', STR_PAD_LEFT),
                'payment_mode' => 'online',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('handled_by');
            $table->dropColumn([
                'reference', 'payment_mode', 'contact_phone', 'delivery_address',
                'delivery_city', 'delivery_fee', 'customer_note', 'delivered_at',
            ]);
        });
    }
};

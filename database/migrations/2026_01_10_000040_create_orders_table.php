<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Commande d'un produit (boutique) ou d'une annonce marketplace.
 * L'achat d'une formation passe par "enrollments", pas ici.
 * status : paiement | validee | expediee | refuse
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name');
            $table->nullableMorphs('orderable');          // ShopProduct | MarketplaceListing
            $table->string('item_label');                 // "Engrais organique ×2"
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->unsignedInteger('amount');            // FCFA
            $table->string('status', 20)->default('paiement')->index();
            $table->timestamp('ordered_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

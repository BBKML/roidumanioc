<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crop_orders', function (Blueprint $table) {
            // Validation admin obligatoire avant que les conditions du producteur
            // n'atteignent l'acheteur — valeurs soumises par le producteur, en attente,
            // jamais visibles de l'acheteur tant que l'admin n'a pas approuvé (déplacées
            // vers product_price_total / crop_order_delivery_proposals à l'approbation).
            $table->unsignedInteger('pending_product_price_total')->nullable()->after('delivery_conditions_note');
            $table->unsignedInteger('pending_delivery_fee')->nullable()->after('pending_product_price_total');
            $table->text('admin_review_note')->nullable()->after('pending_delivery_fee');

            // Livraison auto-organisée (le client a son propre moyen de venir récupérer, ou
            // le producteur a son propre livreur) — alternative à l'aide livraison admin.
            $table->string('self_arranged_mode', 20)->nullable()->after('admin_review_note');
            $table->foreignId('self_arranged_by')->nullable()->after('self_arranged_mode')->constrained('users')->nullOnDelete();
            $table->timestamp('self_arranged_at')->nullable()->after('self_arranged_by');
        });
    }

    public function down(): void
    {
        Schema::table('crop_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('self_arranged_by');
            $table->dropColumn([
                'pending_product_price_total', 'pending_delivery_fee', 'admin_review_note',
                'self_arranged_mode', 'self_arranged_at',
            ]);
        });
    }
};

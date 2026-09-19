<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crop_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crop_offer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('producer_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('buyer_profile_id')->constrained()->cascadeOnDelete();

            // Instantané du produit au moment de la commande — l'offre d'origine peut être
            // modifiée/archivée ensuite (même logique que Collaboration::agreed_product).
            $table->string('product_name');
            $table->string('variety')->nullable();

            $table->decimal('requested_quantity', 10, 2);
            $table->string('requested_unit', 20);
            $table->string('delivery_location');
            $table->date('desired_date')->nullable();
            $table->text('delivery_notes')->nullable();

            // Texte libre depuis une liste fermée + « Autre » côté formulaire — jamais
            // App\Enums\PaymentMethod, réservé aux paiements plateforme (voir CLAUDE.md,
            // même raisonnement que collaboration_payments.method).
            $table->string('payment_method', 100);
            $table->string('quality_expected')->nullable();
            $table->text('buyer_message')->nullable();

            $table->string('status', 30)->default('en_attente_producteur')->index();
            $table->text('refusal_reason')->nullable();

            // Renseigné une seule fois par le producteur (setDeliveryConditions()) — prix
            // TOTAL du produit pour la quantité demandée, jamais renégocié ensuite.
            $table->unsignedInteger('product_price_total')->nullable();
            $table->text('delivery_conditions_note')->nullable();

            // Renseignés seulement une fois une proposition de frais de livraison acceptée.
            $table->unsignedInteger('delivery_fee_agreed')->nullable();
            $table->unsignedInteger('total_amount')->nullable();

            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('delivery_assist_requested_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['producer_profile_id', 'buyer_profile_id']);
            $table->index(['buyer_profile_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crop_orders');
    }
};

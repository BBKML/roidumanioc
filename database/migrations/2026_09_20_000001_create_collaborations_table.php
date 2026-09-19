<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Formalise l'accord une fois une ConnectionRequest confirmée (collaboration_confirmee).
 * Flux pair-à-pair : l'acheteur déclare le paiement, le PRODUCTEUR confirme — Le Roi du
 * Manioc ne collecte pas l'argent ici (§18), volontairement distinct d'Order/Payment qui
 * modélisent le flux où l'admin seul confirme un encaissement de la plateforme.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collaborations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('connection_request_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('producer_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('buyer_profile_id')->constrained()->cascadeOnDelete();
            $table->string('agreed_product');
            $table->decimal('agreed_quantity', 10, 2);
            $table->string('agreed_unit', 20);
            $table->unsignedInteger('agreed_price_total')->nullable();
            $table->text('terms_note')->nullable();
            $table->string('status', 30)->default('en_cours')->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collaborations');
    }
};

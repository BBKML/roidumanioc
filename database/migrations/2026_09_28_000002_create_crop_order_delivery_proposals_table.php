<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Historique complet de la négociation des frais de livraison (§6/§7) — chaque
        // proposition est une NOUVELLE ligne, jamais un écrasement, et garde son propre
        // statut (contrairement au précédent conversation_messages/proposal_terms où
        // seule la dernière proposition du fil comptait).
        Schema::create('crop_order_delivery_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crop_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('proposed_by')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('amount');
            $table->text('note')->nullable();
            $table->string('status', 20)->default('en_attente')->index();
            $table->timestamps();

            $table->index(['crop_order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crop_order_delivery_proposals');
    }
};

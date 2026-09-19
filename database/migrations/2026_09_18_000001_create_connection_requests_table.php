<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mise en relation entre un producteur et un acheteur, à propos d'une offre ou d'un
 * besoin (exactement l'un des deux — vérifié dans ConnectionRequest::booted(), pas ici :
 * une contrainte CHECK portable entre moteurs SQL n'apporterait rien de plus). Machine à
 * états : voir App\Enums\ConnectionRequestStatus.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('connection_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('requester_role', 20); // producteur | acheteur — qui a initié
            $table->foreignId('crop_offer_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('buyer_need_id')->nullable()->constrained()->cascadeOnDelete();
            // Résolus et stockés à la création (simplifie les requêtes des boîtes de réception).
            $table->foreignId('producer_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('buyer_profile_id')->constrained()->cascadeOnDelete();
            $table->string('status', 30)->default('en_attente')->index();
            $table->text('message_initial')->nullable();
            $table->timestamps();

            $table->index(['producer_profile_id', 'buyer_profile_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('connection_requests');
    }
};

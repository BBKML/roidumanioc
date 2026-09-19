<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Photos d'une offre — table enfant (plutôt qu'un JSON) pour rester cohérent avec le
 * reste du schéma. Maximum 5 photos par offre, imposé côté composant Livewire.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crop_offer_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crop_offer_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->unsignedTinyInteger('position')->default(0);
            $table->timestamps();

            $table->index(['crop_offer_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crop_offer_photos');
    }
};

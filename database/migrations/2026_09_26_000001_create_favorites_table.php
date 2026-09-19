<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Favoris d'un acheteur sur un producteur (§11 — tableau de bord acheteur). Table pivot
 * simple consommée via User::favoriteProducers() (belongsToMany + toggle()/detach()) —
 * pas de modèle Eloquent dédié, aucune colonne au-delà des timestamps ne le justifie.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('producer_profile_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'producer_profile_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};

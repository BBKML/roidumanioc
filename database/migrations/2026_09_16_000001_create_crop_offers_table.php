<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Offres publiées par un producteur (récoltes, boutures, transformé, intrants).
 * Alimente le catalogue de recherche (Phase 4) et la mise en relation (Phase 5).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crop_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producer_profile_id')->constrained()->cascadeOnDelete();
            $table->string('product_name');
            $table->string('variety')->nullable();
            $table->decimal('quantity', 10, 2);
            $table->string('unit', 20); // kg | sac | tonne | unite
            $table->unsignedInteger('price_indicative')->nullable(); // FCFA, "à convenir" si nul
            $table->string('location');
            $table->boolean('is_available')->default(true);
            $table->date('available_from')->nullable();
            $table->text('description')->nullable();
            $table->string('status', 20)->default('brouillon')->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crop_offers');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Besoins d'achat publiés par un acheteur — symétrique de crop_offers (Phase 2).
 * Alimentera le catalogue de recherche (Phase 4) et la mise en relation (Phase 5).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buyer_needs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buyer_profile_id')->constrained()->cascadeOnDelete();
            $table->string('product_wanted');
            $table->decimal('quantity', 10, 2);
            $table->string('unit', 20); // réutilise le vocabulaire de CropUnit : kg | sac | tonne | unite
            $table->string('location');
            $table->date('wanted_date')->nullable();
            $table->string('frequency', 20); // ponctuel | recurrent
            $table->string('quality_desc')->nullable();
            $table->unsignedInteger('budget_indicative')->nullable(); // FCFA, "à convenir" si nul
            $table->text('description')->nullable();
            $table->string('status', 20)->default('ouvert')->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('buyer_needs');
    }
};

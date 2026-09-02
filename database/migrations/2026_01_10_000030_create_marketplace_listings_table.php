<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Annonces de la marketplace (déposées par les producteurs, modérées par l'admin).
 * status : en_attente | validee | refuse
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');                       // Récolte, Bouture, Transformé, Intrant
            $table->string('title');
            $table->string('location')->nullable();
            $table->string('price_label');                // "100 FCFA / Kg"
            $table->string('seller_name');
            $table->boolean('is_official')->default(false);
            $table->string('image_path')->nullable();
            $table->string('status', 20)->default('en_attente')->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_listings');
    }
};

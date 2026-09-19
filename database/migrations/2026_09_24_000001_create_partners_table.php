<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Partenaires (logos défilant sur /communaute) — modèle dédié, même famille que
 * `testimonials`/`awards` : l'admin en ajoute/retire au fil du temps depuis le même
 * écran CMS, pas un contenu à taille fixe comme les 4 piliers.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('logo_path')->nullable();
            $table->boolean('is_published')->default(true)->index();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partners');
    }
};

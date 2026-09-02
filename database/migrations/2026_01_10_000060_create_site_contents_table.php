<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contenu éditorial du site vitrine (CMS).
 * Une ligne par section : key = "hero", "mission", "piliers", "placali", "pied"...
 * data = JSON de la section (mêmes clés que siteContent[key] de la maquette).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_contents', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();          // seo, entete, hero, bandeau, mission, chiffres, piliers, placali, formations_section, marketplace_section, communaute, distinctions, fondateur, cta, pied
            $table->string('label');                  // libellé affiché dans l'admin
            $table->json('data');
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_contents');
    }
};

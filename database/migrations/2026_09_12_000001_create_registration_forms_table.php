<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pages « formulaire d'inscription » publiques (une par campagne) — partagées en lien
 * direct sous les vidéos réseaux sociaux. Contenu 100 % éditable depuis l'admin,
 * champs du formulaire fixes (voir registration_leads).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_forms', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('status')->default('brouillon');
            $table->string('subtitle')->nullable();
            $table->string('cover_image_path')->nullable();
            $table->string('whatsapp_number')->nullable();
            $table->text('intro')->nullable();
            $table->text('objectives')->nullable();
            $table->text('schedule_info')->nullable();
            $table->text('program')->nullable();
            $table->text('certifications')->nullable();
            $table->string('price_amount')->nullable();
            $table->text('price_note')->nullable();
            $table->text('payment_methods')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_forms');
    }
};

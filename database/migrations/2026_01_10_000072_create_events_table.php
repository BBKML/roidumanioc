<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Événements : lives, ateliers, visites de terrain.
 * status : planifie | termine
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('date_label')->nullable();     // "12 sept. 2026" (affichage libre)
            $table->timestamp('starts_at')->nullable();    // date réelle (tri, rappels)
            $table->string('type');                        // Live, Atelier, Visite
            $table->string('status', 20)->default('planifie')->index();
            $table->string('link')->nullable();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};

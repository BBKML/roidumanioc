<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ressources jointes à une leçon (PDF, fiches, images).
 * Stockées sur le disque privé, téléchargées via une route gardée par l'inscription.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('disk', 20)->default('local');
            $table->string('path');
            $table->string('mime', 100)->nullable();
            $table->unsignedInteger('size')->default(0);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->index(['lesson_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_attachments');
    }
};

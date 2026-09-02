<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Inscription d'un apprenant à une formation.
 * status : paiement (en attente de vérification) | validee | refuse
 * Les formations gratuites créent directement une inscription "validee".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('formation_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('paiement')->index();
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'formation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};

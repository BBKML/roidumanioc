<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Profil producteur, superposé à un compte existant (rôle/statut inchangés).
 * Un seul profil par utilisateur (user_id unique).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('business_name');
            $table->text('bio')->nullable();
            $table->string('zone');
            $table->string('activity_type'); // recolte | bouture | transforme | intrant
            $table->string('capacity_note')->nullable();
            $table->unsignedSmallInteger('years_active')->nullable();
            $table->string('logo_path')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producer_profiles');
    }
};

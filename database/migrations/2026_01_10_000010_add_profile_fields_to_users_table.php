<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Champs métier ajoutés au compte utilisateur.
 * role   : admin | apprenant           (accès au tableau de bord ou à l'espace apprenant)
 * status : actif | en_attente | suspendu
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('city')->nullable()->after('phone');
            $table->string('role', 20)->default('apprenant')->after('city')->index();
            $table->string('status', 20)->default('actif')->after('role')->index();
            $table->string('provider')->nullable()->after('status');      // "google", ...
            $table->string('provider_id')->nullable()->after('provider');
            $table->timestamp('joined_at')->nullable()->after('provider_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'city', 'role', 'status', 'provider', 'provider_id', 'joined_at']);
        });
    }
};

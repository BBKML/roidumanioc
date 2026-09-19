<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Trace la dernière définition de mot de passe par l'utilisateur lui-même.
 * - null + provider=google  => compte « Google uniquement » (pas de mot de passe choisi)
 * - sert aussi à l'audit et à l'invalidation des autres sessions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('password_changed_at')->nullable()->after('password');
        });

        // Les comptes existants (créés avec mot de passe) sont considérés « définis ».
        DB::table('users')
            ->whereNull('provider')
            ->update(['password_changed_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('password_changed_at');
        });
    }
};

<?php

use App\Support\PhoneNumber;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Connexion possible par e-mail OU par numéro de téléphone : beaucoup de clients
 * n'ont pas d'e-mail mais ont tous un téléphone. L'e-mail devient donc facultatif
 * (au moins l'un des deux reste obligatoire, contrôlé en validation applicative)
 * et le téléphone devient un identifiant unique, normalisé (chiffres uniquement).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Normalise les numéros déjà en base avant de poser la contrainte unique.
        foreach (DB::table('users')->whereNotNull('phone')->get(['id', 'phone']) as $user) {
            $normalized = PhoneNumber::normalize($user->phone);

            DB::table('users')->where('id', $user->id)->update(['phone' => $normalized]);
        }

        // Doublons éventuels après normalisation : on vide plutôt que planter la migration.
        DB::table('users')
            ->select('phone')
            ->whereNotNull('phone')
            ->groupBy('phone')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('phone')
            ->each(function (string $phone) {
                DB::table('users')->where('phone', $phone)->update(['phone' => null]);
            });

        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->unique('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['phone']);
            $table->string('email')->nullable(false)->change();
        });
    }
};

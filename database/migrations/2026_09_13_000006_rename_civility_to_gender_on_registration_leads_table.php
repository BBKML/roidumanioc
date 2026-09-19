<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Le client préfère un champ « Genre » (Homme/Femme) à l'ancien « Civilité » (M./Mme).
 * On renomme la colonne et on convertit les valeurs déjà enregistrées pour rester cohérent
 * avec les nouvelles soumissions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registration_leads', function (Blueprint $table) {
            $table->renameColumn('civility', 'gender');
        });

        DB::table('registration_leads')->where('gender', 'M.')->update(['gender' => 'Homme']);
        DB::table('registration_leads')->where('gender', 'Mme')->update(['gender' => 'Femme']);
    }

    public function down(): void
    {
        DB::table('registration_leads')->where('gender', 'Homme')->update(['gender' => 'M.']);
        DB::table('registration_leads')->where('gender', 'Femme')->update(['gender' => 'Mme']);

        Schema::table('registration_leads', function (Blueprint $table) {
            $table->renameColumn('gender', 'civility');
        });
    }
};

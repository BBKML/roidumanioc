<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Questions de qualification ajoutées au formulaire public (niveau d'expérience,
 * tranche d'âge) + e-mail rendu facultatif (le téléphone suffit à recontacter le prospect).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registration_leads', function (Blueprint $table) {
            $table->string('experience_level')->nullable()->after('country_code');
            $table->string('age_range')->nullable()->after('experience_level');
        });

        Schema::table('registration_leads', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('registration_leads', function (Blueprint $table) {
            $table->dropColumn(['experience_level', 'age_range']);
        });

        Schema::table('registration_leads', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
        });
    }
};

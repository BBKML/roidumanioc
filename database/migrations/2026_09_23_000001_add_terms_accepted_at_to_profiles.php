<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Preuve d'acceptation des CGU/politique de confidentialité à l'activation d'un espace
 * producteur/acheteur (§44) — une case à cocher sans trace horodatée ne vaudrait rien en
 * cas de litige. `null` = jamais accepté (profils créés avant cette phase).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('producer_profiles', function (Blueprint $table) {
            $table->timestamp('terms_accepted_at')->nullable()->after('logo_path');
        });
        Schema::table('buyer_profiles', function (Blueprint $table) {
            $table->timestamp('terms_accepted_at')->nullable()->after('bio');
        });
    }

    public function down(): void
    {
        Schema::table('producer_profiles', function (Blueprint $table) {
            $table->dropColumn('terms_accepted_at');
        });
        Schema::table('buyer_profiles', function (Blueprint $table) {
            $table->dropColumn('terms_accepted_at');
        });
    }
};

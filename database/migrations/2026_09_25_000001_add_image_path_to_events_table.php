<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajoute une image aux événements (§ demande client — publier les activités à venir
 * avec image/texte/date). Le modèle `events` existe déjà (lives, ateliers, visites),
 * géré depuis /admin/evenements et affiché sur le tableau de bord apprenant — cette
 * migration ne fait qu'y ajouter le champ manquant, sans toucher au reste du schéma.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }
};

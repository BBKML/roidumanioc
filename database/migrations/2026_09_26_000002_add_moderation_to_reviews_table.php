<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modération admin des avis (§25 — administration/marketplace/évaluations), sans jamais
 * éditer/supprimer le contenu déposé (cf. Review — immuable après soumission) : ces
 * colonnes permettent seulement de MASQUER un avis abusif de l'affichage/des moyennes
 * publiques (App\Models\Review::hide()/unhide(), même patron que ProducerProfile::verify()
 * — jamais renseignables par l'utilisateur, réservées à un forceFill admin).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->timestamp('hidden_at')->nullable()->after('comment');
            $table->foreignId('hidden_by')->nullable()->after('hidden_at')->constrained('users')->nullOnDelete();
            $table->string('hidden_reason', 500)->nullable()->after('hidden_by');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropConstrainedForeignId('hidden_by');
            $table->dropColumn(['hidden_at', 'hidden_reason']);
        });
    }
};

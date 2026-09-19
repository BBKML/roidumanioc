<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cartes de proposition dans la messagerie (Phase 14 — négociation façon WhatsApp) :
 * `type` distingue un message ordinaire ('texte', valeur par défaut de l'historique
 * existant) d'une proposition structurée ('proposition'). `type` reste une colonne
 * string simple plutôt qu'un enum PHP dédié — même raisonnement que `BuyerNeed.frequency`
 * (deux valeurs, jamais réutilisées ailleurs, validées par `Rule::in()`).
 * `proposal_terms` (JSON, uniquement rempli quand `type = proposition`) porte les champs
 * structurés (quantité/unité/prix) — `body` reste la note libre qui accompagne la
 * proposition et continue de passer par ContactDetector comme n'importe quel message.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversation_messages', function (Blueprint $table) {
            $table->string('type', 20)->default('texte')->after('sender_id');
            $table->json('proposal_terms')->nullable()->after('body');
        });
    }

    public function down(): void
    {
        Schema::table('conversation_messages', function (Blueprint $table) {
            $table->dropColumn(['type', 'proposal_terms']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Termes de proposition capturés dès le premier contact (quantité/unité/prix), quand le
 * demandeur les indique tout de suite plutôt que d'attendre le statut `negociation` pour
 * proposer via le chat. Transformés en vraie proposition (ConversationMessage) dès que
 * l'autre partie clique « Accepter » — voir ConnectionRequest::initial_proposal_terms et
 * Connect\Show::accept().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('connection_requests', function (Blueprint $table) {
            $table->json('initial_proposal_terms')->nullable()->after('message_initial');
        });
    }

    public function down(): void
    {
        Schema::table('connection_requests', function (Blueprint $table) {
            $table->dropColumn('initial_proposal_terms');
        });
    }
};

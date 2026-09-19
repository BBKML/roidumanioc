<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5 — vérification manuelle & anti-fraude.
 *
 * - proof_hash / proof_mime : empreinte du reçu téléversé (détection de réutilisation).
 * - rejection_reason        : motif communiqué à l'apprenant en cas de refus.
 * - quantity                : pour les commandes de produits (montant = prix × quantité).
 * - index sur transaction_id : détection des numéros de transaction réutilisés.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('proof_hash', 64)->nullable()->after('proof_path')->index();
            $table->string('proof_mime', 100)->nullable()->after('proof_hash');
            $table->string('rejection_reason')->nullable()->after('check_result');
            $table->unsignedSmallInteger('quantity')->default(1)->after('amount');
            $table->index('transaction_id');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['transaction_id']);
            $table->dropIndex(['proof_hash']);
            $table->dropColumn(['proof_hash', 'proof_mime', 'rejection_reason', 'quantity']);
        });
    }
};

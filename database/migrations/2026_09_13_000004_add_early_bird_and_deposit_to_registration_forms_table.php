<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Offre « premiers inscrits » (compte à rebours) + réservation de place à acompte réduit,
 * dans la section « Votre investissement » du formulaire public. Les deux sont facultatifs
 * et propres à chaque formulaire (campagne) : une échéance ou un acompte non renseignés
 * n'affichent tout simplement pas le bloc correspondant, jamais de valeur fictive par défaut.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registration_forms', function (Blueprint $table) {
            $table->timestamp('early_bird_deadline')->nullable()->after('payment_methods');
            $table->string('deposit_amount')->nullable()->after('early_bird_deadline');
            $table->text('deposit_note')->nullable()->after('deposit_amount');
        });
    }

    public function down(): void
    {
        Schema::table('registration_forms', function (Blueprint $table) {
            $table->dropColumn(['early_bird_deadline', 'deposit_amount', 'deposit_note']);
        });
    }
};

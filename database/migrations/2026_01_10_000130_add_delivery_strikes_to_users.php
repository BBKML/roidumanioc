<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Compteur de « refus à la livraison » (client absent / n'a pas payé à la remise).
 * À 2, le paiement à la livraison est désactivé pour ce client — il doit payer en ligne.
 * L'admin peut le remettre à zéro depuis Membres.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('delivery_strikes')->default(0)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('delivery_strikes');
        });
    }
};

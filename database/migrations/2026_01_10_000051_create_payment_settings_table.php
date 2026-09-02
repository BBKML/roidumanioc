<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Comptes marchands affichés au client sur la page de paiement.
 * Table à une seule ligne (id = 1).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_settings', function (Blueprint $table) {
            $table->id();
            $table->string('whatsapp')->nullable();       // numéro qui reçoit les preuves (format international)
            $table->string('wave')->nullable();
            $table->string('orange')->nullable();
            $table->string('mtn')->nullable();
            $table->string('moov')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('rib')->nullable();
            $table->string('intl_link')->nullable();      // Flutterwave / PayPal
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_settings');
    }
};

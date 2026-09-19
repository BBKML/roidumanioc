<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Prospects captés par un formulaire d'inscription public (registration_forms).
 * Suivi manuel par l'admin (WhatsApp/e-mail) — pas de lien avec Enrollment/Payment :
 * ce sont des campagnes externes (réseaux sociaux), pas des inscriptions au catalogue interne.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_form_id')->constrained()->cascadeOnDelete();
            $table->string('civility')->nullable();
            $table->string('last_name');
            $table->string('first_name');
            $table->string('email');
            $table->string('phone_1');
            $table->string('phone_2')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('country_code')->nullable();
            $table->string('profession')->nullable();
            $table->string('company')->nullable();
            $table->string('city_country')->nullable();
            $table->string('motivations')->nullable();
            $table->text('expectations')->nullable();
            $table->string('how_heard')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_frequency')->nullable();
            $table->string('status')->default('nouveau')->index();
            $table->text('admin_note')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_leads');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Déclaration/confirmation de paiement PAIR-À-PAIR — `method` est un texte libre
 * (« Orange Money », « espèces »…), pas App\Enums\PaymentMethod : ce dernier décrit les
 * canaux vers la plateforme (Payment/DeclarePayment), une sémantique différente (§18).
 * `note` porte le texte libre de la déclaration puis, si contesté, le motif du producteur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collaboration_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collaboration_id')->constrained()->cascadeOnDelete();
            $table->foreignId('declared_by')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('amount_declared');
            $table->string('method', 60);
            $table->text('note')->nullable();
            $table->timestamp('declared_at');
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->string('status', 20)->default('declare');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collaboration_payments');
    }
};

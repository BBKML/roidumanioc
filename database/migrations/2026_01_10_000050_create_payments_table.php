<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Preuve de paiement soumise par l'apprenant, vérifiée manuellement par l'admin.
 *
 * payable_*   : ce qui est acheté (Formation | ShopProduct | MarketplaceListing)
 * enrollment_id / order_id : l'enregistrement débloqué à la confirmation
 * status      : a_verifier | confirme | refuse
 * check_result: résultat du contrôle automatique (montant déclaré vs attendu)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();               // RDM-XXXX
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->morphs('payable');                            // Formation | ShopProduct | MarketplaceListing
            $table->foreignId('enrollment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();

            $table->string('label');
            $table->unsignedInteger('amount');                    // montant attendu (FCFA)
            $table->string('method');                             // Wave, Orange Money, MTN MoMo, Moov Money, Virement bancaire, Carte / International

            $table->string('status', 20)->default('a_verifier')->index();

            $table->unsignedInteger('declared_amount')->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('proof_path')->nullable();
            $table->json('check_result')->nullable();

            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

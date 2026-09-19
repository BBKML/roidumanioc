<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sous-objet 1-ligne-par-commande, mirroring exact de collaboration_deliveries —
        // n'existe qu'une fois la commande confirmée et l'aide à la livraison demandée.
        Schema::create('delivery_assists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crop_order_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->string('status', 30)->default('demande_aide')->index();
            $table->string('courier_name')->nullable();
            $table->string('courier_contact')->nullable();
            $table->text('admin_note')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('requested_at');
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_assists');
    }
};

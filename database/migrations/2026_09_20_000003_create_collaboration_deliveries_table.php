<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Un simple statut de suivi de livraison, pas un module logistique. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collaboration_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collaboration_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('prevue');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collaboration_deliveries');
    }
};

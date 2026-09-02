<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Distinctions & reconnaissances (Éléphant d'Or, OPAJEF, IPAB, partenariats...).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('awards', function (Blueprint $table) {
            $table->id();
            $table->string('year')->nullable();          // "2025" ou "—"
            $table->string('title');
            $table->text('description')->nullable();
            $table->boolean('is_published')->default(true)->index();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('awards');
    }
};

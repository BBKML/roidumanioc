<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('formations', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('category');                       // Culture, Fertilisation, Transformation, Gestion
            $table->text('description')->nullable();
            $table->unsignedInteger('price')->default(0);     // FCFA (0 = gratuit)
            $table->string('access', 20)->default('premium'); // gratuit | premium
            $table->string('status', 20)->default('brouillon')->index(); // brouillon | publiee
            $table->string('image_path')->nullable();
            $table->string('duration_label')->nullable();     // "5 h 30"
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('formations');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Évaluation bidirectionnelle en fin de collaboration (§21/§23). `criteria` (JSON) a des
 * clés différentes selon `direction` — voir App\Enums\ReviewDirection::criteria().
 * Immuable après soumission (aucune colonne updated_at exploitée pour une édition —
 * la mise à jour reste techniquement possible en base mais rien dans l'app ne l'expose,
 * cf. Review::class / SubmitReview).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collaboration_id')->constrained()->cascadeOnDelete();
            $table->foreignId('rater_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('ratee_id')->constrained('users')->cascadeOnDelete();
            $table->string('direction', 30);
            $table->unsignedTinyInteger('rating');
            $table->json('criteria');
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['collaboration_id', 'rater_id']);
            $table->index(['ratee_id', 'direction']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};

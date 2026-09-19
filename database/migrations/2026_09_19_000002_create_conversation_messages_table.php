<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Messages d'une conversation. `body` garde toujours le texte tel quel (traçabilité
 * admin) — seul l'AFFICHAGE aux deux parties masque les coordonnées détectées, via
 * ConversationMessage::displayBody() + flagged_patterns (jamais stocké masqué).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversation_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->boolean('contains_flagged_content')->default(false);
            $table->json('flagged_patterns')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_messages');
    }
};

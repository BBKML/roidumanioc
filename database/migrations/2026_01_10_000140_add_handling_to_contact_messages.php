<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Boîte de réception : statut (nouveau / lu / traité / spam), suivi et réponse.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1) L'ancien index sur `handled` doit partir avant qu'on touche à la colonne (SQLite).
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropIndex(['handled']);
        });

        // 2) Nouvelles colonnes.
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->string('status', 16)->default('nouveau')->after('message');
            $table->text('admin_note')->nullable()->after('status');
            $table->timestamp('handled_at')->nullable()->after('admin_note');
            $table->foreignId('handled_by')->nullable()->after('handled_at')->constrained('users')->nullOnDelete();
            $table->timestamp('replied_at')->nullable()->after('handled_by');
        });

        // 3) Reprise de l'existant.
        DB::table('contact_messages')->where('handled', true)
            ->update(['status' => 'traite', 'handled_at' => now()]);

        // 4) On retire l'ancienne colonne, puis on indexe le nouveau statut.
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropColumn('handled');
        });
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropIndex(['status']);
        });
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->boolean('handled')->default(false)->after('message');
            $table->dropConstrainedForeignId('handled_by');
            $table->dropColumn(['status', 'admin_note', 'handled_at', 'replied_at']);
        });
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->index('handled');
        });
    }
};

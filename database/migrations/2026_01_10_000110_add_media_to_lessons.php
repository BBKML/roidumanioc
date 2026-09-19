<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Source vidéo d'une leçon, pensée pour durer :
 *  - none   : pas de vidéo
 *  - link   : lien externe (YouTube, Vimeo…) — auto-détecté par Lesson::embedUrl()
 *  - bunny  : Bunny Stream (GUID ou URL d'intégration) — lecture signée
 *  - upload : fichier téléversé, stocké sur `video_disk` (privé), servi via une route gardée
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->string('video_provider', 20)->default('none')->after('type');
            $table->string('video_disk', 20)->nullable()->after('video_url');
            $table->string('video_path')->nullable()->after('video_disk');
        });

        // Rétro-compat : les liens déjà saisis deviennent des sources « link »
        // (ou « bunny » si l'URL ressemble à du Bunny Stream).
        DB::table('lessons')->whereNotNull('video_url')->where('video_url', '!=', '')
            ->orderBy('id')->each(function ($row) {
                $bunny = str_contains((string) $row->video_url, 'mediadelivery.net')
                    || preg_match('~^[0-9a-f-]{36}$~i', trim((string) $row->video_url));

                DB::table('lessons')->where('id', $row->id)
                    ->update(['video_provider' => $bunny ? 'bunny' : 'link']);
            });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn(['video_provider', 'video_disk', 'video_path']);
        });
    }
};

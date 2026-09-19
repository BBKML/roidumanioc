<?php

use App\Models\SiteContent;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Convertit le contenu déjà enregistré (chaque champ texte/textarea/html/liste = une seule
 * valeur française) vers la forme bilingue {"fr": "...", "en": "..."} attendue depuis l'ajout
 * du choix de langue FR/EN sur la vitrine. Les champs "image" ne sont pas concernés.
 * Sans effet sur une base fraîche : le seeder écrit déjà directement au nouveau format.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->transform(fn (array $data, string $key) => SiteContent::wrapMonolingual($data, $key));
    }

    public function down(): void
    {
        $schema = require app_path('Livewire/Admin/content-sections.php');

        $this->transform(function (array $data, string $key) use ($schema) {
            $sectionSchema = $schema[$key] ?? null;

            return $sectionSchema ? SiteContent::localizeSection($data, $sectionSchema, 'fr') : $data;
        });
    }

    private function transform(callable $fn): void
    {
        DB::table('site_contents')->orderBy('id')->get()->each(function ($row) use ($fn) {
            $data = json_decode($row->data, true) ?? [];

            DB::table('site_contents')->where('id', $row->id)->update([
                'data' => json_encode($fn($data, $row->key)),
            ]);
        });
    }
};

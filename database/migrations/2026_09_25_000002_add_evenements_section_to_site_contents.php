<?php

use App\Models\SiteContent;
use Illuminate\Database\Migrations\Migration;

/**
 * Nouvelle section CMS pour la page publique /evenements (page « activités à venir »
 * demandée par le client). Insère la ligne `site_contents` manquante : sans elle,
 * l'écran d'admin (ContentManager::save()) échouerait avec un 404 sur une base déjà
 * migrée avant l'ajout de cette section — même besoin que pour toute nouvelle section
 * CMS ajoutée après le seeder initial.
 */
return new class extends Migration
{
    public function up(): void
    {
        SiteContent::updateOrCreate(
            ['key' => 'evenements_section'],
            [
                'label' => 'Section Événements (titres)',
                'position' => (int) SiteContent::max('position') + 1,
                'data' => SiteContent::wrapMonolingual([
                    'eyebrow' => 'Événements & rencontres',
                    'title' => 'Les prochains<br><em>rendez-vous</em> du royaume.',
                    'lead' => 'Rencontres, formations en présentiel, foires et journées portes ouvertes : suivez ici les activités à venir du Roi du Manioc.',
                ], 'evenements_section'),
            ]
        );
    }

    public function down(): void
    {
        SiteContent::where('key', 'evenements_section')->delete();
    }
};

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Contenu de base — toujours exécuté (aussi en production).
     * Pour la démo (inscriptions, paiements à vérifier) :
     *   php artisan db:seed --class=DemoSeeder
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            FormationSeeder::class,
            CatalogSeeder::class,
            CommunitySeeder::class,
            PaymentSettingSeeder::class,
            SiteContentSeeder::class,
        ]);

        if (app()->environment('local', 'testing')) {
            $this->call(DemoSeeder::class);
        }
    }
}

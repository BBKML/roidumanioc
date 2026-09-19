<?php

namespace Database\Seeders;

use App\Models\Formation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FormationSeeder extends Seeder
{
    public function run(): void
    {
        $formations = [
            [
                'title' => 'Réussir la culture du manioc',
                'category' => 'Culture', 'price' => 15000, 'status' => 'publiee',
                'image_path' => 'img/champ-manioc.jpg', 'duration_label' => '5 h 30',
                'description' => 'De la plantation à la récolte, toutes les étapes expliquées.',
                'lessons' => [
                    'Choisir sa parcelle et ses variétés',
                    'Préparer le sol correctement',
                    'Planter les boutures',
                    'Entretien et désherbage',
                    'Récolte et conservation',
                    'Quiz — la culture du manioc',
                ],
            ],
            [
                'title' => 'Fertilisation et rendement',
                'category' => 'Fertilisation', 'price' => 12000, 'status' => 'publiee',
                'image_path' => 'img/tubercule-geant.jpg', 'duration_label' => '3 h 15',
                'description' => 'Techniques pour augmenter vos rendements naturellement.',
                'lessons' => [
                    'Comprendre les besoins du manioc',
                    'Fertilisation organique',
                    "Calendrier d'apport",
                    'Quiz — fertilisation',
                ],
            ],
            [
                'title' => 'Transformation du manioc',
                'category' => 'Transformation', 'price' => 15000, 'status' => 'publiee',
                'image_path' => 'img/attieke-marche.jpg', 'duration_label' => '4 h 20',
                'description' => 'Attiéké, placali, gari : transformez et valorisez votre production.',
                'lessons' => [
                    'Hygiène et matériel',
                    'Fabriquer le placali',
                    "Fabriquer l'attiéké",
                    'Quiz — transformation',
                ],
            ],
            [
                'title' => 'Gestion et commercialisation',
                'category' => 'Gestion', 'price' => 10000, 'status' => 'brouillon',
                'image_path' => 'img/entrepreneur-produit.jpg', 'duration_label' => '3 h 10',
                'description' => 'Trouvez des marchés et vendez vos produits au meilleur prix.',
                'lessons' => [
                    'Calculer son prix de revient',
                    'Trouver des acheteurs',
                    'Négocier et livrer',
                ],
            ],
            [
                'title' => 'Les bases de la culture du manioc',
                'category' => 'Culture', 'price' => 0, 'status' => 'publiee',
                'image_path' => 'img/formation-champ.jpg', 'duration_label' => '2 h 40',
                'description' => 'Le point de départ pour tout nouveau producteur.',
                'lessons' => [
                    'Le cycle du manioc',
                    'Le climat et le sol',
                    'Le matériel de base',
                    'Les erreurs courantes',
                    'Les variétés',
                    'Quiz — les bases',
                ],
            ],
            [
                'title' => 'Préparation du sol et plantation',
                'category' => 'Culture', 'price' => 0, 'status' => 'publiee',
                'image_path' => 'img/sol-billons.jpg', 'duration_label' => '2 h 10',
                'description' => 'Optimisez chaque étape avant la mise en terre.',
                'lessons' => [
                    'Défrichage et labour',
                    'Billons et buttes',
                    'Espacement des plants',
                    'Quiz — préparation',
                ],
            ],
        ];

        foreach ($formations as $pos => $data) {
            $lessons = $data['lessons'];
            unset($data['lessons']);

            $formation = Formation::updateOrCreate(
                ['slug' => Str::slug($data['title'])],
                array_merge($data, ['slug' => Str::slug($data['title']), 'position' => $pos]),
            );

            $formation->lessons()->delete();
            foreach ($lessons as $i => $title) {
                $isLast = $i === count($lessons) - 1;

                // Démo : la 1re leçon a une vidéo YouTube, les autres restent « à compléter ».
                $video = $i === 0
                    ? ['type' => 'video', 'video_provider' => 'link', 'video_url' => 'https://www.youtube.com/watch?v=aqz-KE-bpKQ']
                    : ['type' => $isLast ? 'quiz' : 'video', 'video_provider' => 'none'];

                $formation->lessons()->create([
                    'title' => $title,
                    'duration_label' => (8 + $i * 4).' min',
                    'position' => $i,
                    'content' => 'Contenu de la leçon « '.$title.' » — à rédiger dans le back-office.',
                    ...$video,
                ]);
            }
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\CommunityPost;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Seeder;

class CommunitySeeder extends Seeder
{
    public function run(): void
    {
        $users = User::pluck('id', 'name');

        $posts = [
            ['author_name' => 'Kouassi Diby', 'body' => "Mes feuilles de manioc jaunissent, qui peut m'aider ?", 'status' => 'visible', 'replies_count' => 8],
            ['author_name' => 'Ahou Serge', 'body' => 'Merci pour la formation sur la fertilisation, mes plants poussent bien !', 'status' => 'visible', 'replies_count' => 3],
            ['author_name' => 'Anonyme', 'body' => 'Message publicitaire non lié au manioc…', 'status' => 'signale', 'replies_count' => 0],
        ];

        CommunityPost::query()->delete();
        foreach ($posts as $p) {
            CommunityPost::create(array_merge($p, ['user_id' => $users[$p['author_name']] ?? null]));
        }

        $events = [
            ['title' => 'Live — Préparer la saison des pluies', 'date_label' => '12 sept. 2026', 'type' => 'Live', 'status' => 'planifie'],
            ['title' => 'Atelier transformation à Yamoussoukro', 'date_label' => '20 sept. 2026', 'type' => 'Atelier', 'status' => 'planifie'],
            ['title' => 'Visite de plantation modèle — Daloa', 'date_label' => '02 sept. 2026', 'type' => 'Visite', 'status' => 'termine'],
        ];

        Event::query()->delete();
        foreach ($events as $pos => $e) {
            Event::create(array_merge($e, ['position' => $pos]));
        }
    }
}

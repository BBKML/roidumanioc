<?php

namespace Database\Seeders;

use App\Models\MarketplaceListing;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $sellers = User::pluck('id', 'name');

        $listings = [
            ['type' => 'Récolte', 'title' => '10 tonnes de manioc frais', 'location' => 'Daloa, Haut-Sassandra',
                'price_label' => '100 FCFA / Kg', 'seller_name' => 'Kouassi Diby', 'status' => 'validee',
                'image_path' => 'img/manioc-frais.jpg'],
            ['type' => 'Bouture', 'title' => 'Boutures améliorées (lot producteur)', 'location' => 'Bouaké, Gbêkê',
                'price_label' => '250 FCFA / unité', 'seller_name' => 'Konan Ismaël', 'status' => 'validee',
                'image_path' => 'img/sol-billons.jpg'],
            ['type' => 'Transformé', 'title' => 'Attiéké artisanal, sac de 5 kg', 'location' => 'Adzopé, La Mé',
                'price_label' => '4 000 FCFA / sac', 'seller_name' => 'Ahou Serge', 'status' => 'en_attente',
                'image_path' => 'img/attieke-marche.jpg'],
            ['type' => 'Transformé', 'title' => 'Gari fin premier choix', 'location' => 'Divo, Lôh-Djiboua',
                'price_label' => '900 FCFA / Kg', 'seller_name' => 'Yao Bruno', 'status' => 'en_attente',
                'image_path' => 'img/attieke-marche.jpg'],
        ];

        MarketplaceListing::query()->delete();
        foreach ($listings as $l) {
            MarketplaceListing::create(array_merge($l, [
                'user_id' => $sellers[$l['seller_name']] ?? null,
                'is_official' => false,
            ]));
        }

        $products = [
            ['name' => 'Boutures de manioc améliorées', 'category' => 'Bouture', 'price' => 500, 'stock' => 1200,
                'image_path' => 'img/sol-billons.jpg'],
            ['name' => 'Engrais organique, sac de 50 kg', 'category' => 'Engrais', 'price' => 12000, 'stock' => 85,
                'image_path' => 'img/intrant-bidon.jpg'],
            ['name' => 'Fongicide naturel, 1 litre', 'category' => 'Fongicide', 'price' => 8000, 'stock' => 40,
                'image_path' => 'img/intrant-bidon.jpg'],
            ['name' => 'Répulsif naturel, 1 litre', 'category' => 'Répulsif', 'price' => 7000, 'stock' => 22,
                'image_path' => 'img/intrant-bidon.jpg'],
        ];

        ShopProduct::query()->delete();
        foreach ($products as $pos => $p) {
            ShopProduct::create(array_merge($p, ['position' => $pos]));
        }
    }
}

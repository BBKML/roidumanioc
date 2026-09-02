<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['name' => 'Seggo Kobenan Michel', 'email' => 'admin@roidumanioc.ci', 'pass' => 'admin',
                'role' => 'admin', 'status' => 'actif', 'city' => 'Yamoussoukro', 'phone' => '07 00 00 00 00', 'since' => '2026-01-15'],
            ['name' => 'Aïcha Coulibaly', 'email' => 'aicha@exemple.ci', 'pass' => 'demo',
                'role' => 'apprenant', 'status' => 'actif', 'city' => 'Yamoussoukro', 'phone' => '07 12 34 56 78', 'since' => '2026-03-04'],
            ['name' => 'Kouassi Diby', 'email' => 'kouassi@exemple.ci', 'pass' => 'demo',
                'role' => 'apprenant', 'status' => 'actif', 'city' => 'Daloa', 'phone' => '05 11 22 33 44', 'since' => '2026-01-10'],
            ['name' => 'Ahou Serge', 'email' => 'ahou@exemple.ci', 'pass' => 'demo',
                'role' => 'apprenant', 'status' => 'actif', 'city' => 'Adzopé', 'phone' => '01 55 66 77 88', 'since' => '2026-02-08'],
            ['name' => 'Mariam Fofana', 'email' => 'mariam@exemple.ci', 'pass' => 'demo',
                'role' => 'apprenant', 'status' => 'en_attente', 'city' => 'Bouaké', 'phone' => '07 99 88 77 66', 'since' => '2026-08-20'],
            ['name' => 'Konan Ismaël', 'email' => 'konan@exemple.ci', 'pass' => 'demo',
                'role' => 'apprenant', 'status' => 'actif', 'city' => 'Korhogo', 'phone' => '05 44 33 22 11', 'since' => '2026-04-12'],
            ['name' => 'Yao Bruno', 'email' => 'yao@exemple.ci', 'pass' => 'demo',
                'role' => 'apprenant', 'status' => 'suspendu', 'city' => 'Divo', 'phone' => '01 22 33 44 55', 'since' => '2025-12-01'],
        ];

        foreach ($rows as $r) {
            User::updateOrCreate(
                ['email' => $r['email']],
                [
                    'name' => $r['name'],
                    'password' => Hash::make($r['pass']),
                    'role' => $r['role'],
                    'status' => $r['status'],
                    'city' => $r['city'],
                    'phone' => $r['phone'],
                    'email_verified_at' => now(),
                    'joined_at' => $r['since'],
                ]
            );
        }
    }
}

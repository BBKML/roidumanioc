<?php

namespace Database\Seeders;

use App\Models\PaymentSetting;
use Illuminate\Database\Seeder;

class PaymentSettingSeeder extends Seeder
{
    public function run(): void
    {
        PaymentSetting::updateOrCreate(['id' => 1], [
            'whatsapp' => '2250700000000',
            'wave' => '07 07 07 07 07',
            'orange' => '07 11 11 11 11',
            'mtn' => '05 22 22 22 22',
            'moov' => '01 33 33 33 33',
            'bank_name' => "Ecobank Côte d'Ivoire",
            'rib' => 'CI93 CI000 01234 5678901234 56',
            'intl_link' => 'https://flutterwave.com/pay/roi-du-manioc',
            'delivery_fee' => 2000,
            'delivery_note' => 'Livraison Abidjan sous 48 h · autres villes : délai et frais à convenir.',
        ]);
    }
}

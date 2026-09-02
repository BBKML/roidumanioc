<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentSetting extends Model
{
    protected $fillable = [
        'whatsapp', 'wave', 'orange', 'mtn', 'moov', 'bank_name', 'rib', 'intl_link',
    ];

    /**
     * Récupère (ou crée) l'unique ligne de configuration.
     */
    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1]);
    }
}

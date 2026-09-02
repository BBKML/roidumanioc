<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Wave = 'wave';
    case OrangeMoney = 'orange_money';
    case MtnMomo = 'mtn_momo';
    case MoovMoney = 'moov_money';
    case Virement = 'virement';
    case Carte = 'carte';

    public function label(): string
    {
        return match ($this) {
            self::Wave => 'Wave',
            self::OrangeMoney => 'Orange Money',
            self::MtnMomo => 'MTN MoMo',
            self::MoovMoney => 'Moov Money',
            self::Virement => 'Virement bancaire',
            self::Carte => 'Carte / International',
        };
    }

    public function isMobileMoney(): bool
    {
        return in_array($this, [self::Wave, self::OrangeMoney, self::MtnMomo, self::MoovMoney], true);
    }
}

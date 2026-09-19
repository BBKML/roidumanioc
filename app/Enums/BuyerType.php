<?php

namespace App\Enums;

enum BuyerType: string
{
    case Transformateur = 'transformateur';
    case Commercant = 'commercant';
    case Restaurant = 'restaurant';
    case Grossiste = 'grossiste';
    case Distributeur = 'distributeur';
    case Autre = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::Transformateur => 'Transformateur',
            self::Commercant => 'Commerçant',
            self::Restaurant => 'Restaurant',
            self::Grossiste => 'Grossiste',
            self::Distributeur => 'Distributeur',
            self::Autre => 'Autre',
        };
    }
}

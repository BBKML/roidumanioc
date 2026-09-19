<?php

namespace App\Enums;

enum BuyerNeedStatus: string
{
    case Ouvert = 'ouvert';
    case Satisfait = 'satisfait';
    case Expire = 'expire';
    case Ferme = 'ferme';

    public function label(): string
    {
        return match ($this) {
            self::Ouvert => 'Ouvert',
            self::Satisfait => 'Satisfait',
            self::Expire => 'Expiré',
            self::Ferme => 'Fermé',
        };
    }
}

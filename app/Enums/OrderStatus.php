<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Paiement = 'paiement';
    case Validee = 'validee';
    case Expediee = 'expediee';
    case Refuse = 'refuse';

    public function label(): string
    {
        return match ($this) {
            self::Paiement => 'Paiement à vérifier',
            self::Validee => 'Validée',
            self::Expediee => 'Expédiée',
            self::Refuse => 'Refusée',
        };
    }
}

<?php

namespace App\Enums;

enum CollaborationPaymentStatus: string
{
    case Declare = 'declare';
    case Confirme = 'confirme';
    case Conteste = 'conteste';

    public function label(): string
    {
        return match ($this) {
            self::Declare => 'Déclaré',
            self::Confirme => 'Confirmé',
            self::Conteste => 'Contesté',
        };
    }
}

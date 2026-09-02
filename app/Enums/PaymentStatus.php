<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case AVerifier = 'a_verifier';
    case Confirme = 'confirme';
    case Refuse = 'refuse';

    public function label(): string
    {
        return match ($this) {
            self::AVerifier => 'À vérifier',
            self::Confirme => 'Confirmé',
            self::Refuse => 'Refusé',
        };
    }
}

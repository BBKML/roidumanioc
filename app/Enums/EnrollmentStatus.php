<?php

namespace App\Enums;

enum EnrollmentStatus: string
{
    case Paiement = 'paiement';   // en attente de vérification du paiement
    case Validee = 'validee';     // accès accordé
    case Refuse = 'refuse';

    public function label(): string
    {
        return match ($this) {
            self::Paiement => 'Paiement à vérifier',
            self::Validee => 'Validée',
            self::Refuse => 'Refusée',
        };
    }

    public function grantsAccess(): bool
    {
        return $this === self::Validee;
    }
}

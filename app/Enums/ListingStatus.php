<?php

namespace App\Enums;

enum ListingStatus: string
{
    case EnAttente = 'en_attente';
    case Validee = 'validee';
    case Refuse = 'refuse';

    public function label(): string
    {
        return match ($this) {
            self::EnAttente => 'En attente',
            self::Validee => 'Publiée',
            self::Refuse => 'Refusée',
        };
    }
}

<?php

namespace App\Enums;

enum CropOfferStatus: string
{
    case Brouillon = 'brouillon';
    case Publiee = 'publiee';
    case Indisponible = 'indisponible';
    case Archivee = 'archivee';

    public function label(): string
    {
        return match ($this) {
            self::Brouillon => 'Brouillon',
            self::Publiee => 'Publiée',
            self::Indisponible => 'Indisponible',
            self::Archivee => 'Archivée',
        };
    }
}

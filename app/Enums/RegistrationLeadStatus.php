<?php

namespace App\Enums;

enum RegistrationLeadStatus: string
{
    case Nouveau = 'nouveau';
    case Contacte = 'contacte';
    case Inscrit = 'inscrit';
    case Abandonne = 'abandonne';

    public function label(): string
    {
        return match ($this) {
            self::Nouveau => 'Nouveau',
            self::Contacte => 'Contacté',
            self::Inscrit => 'Inscrit',
            self::Abandonne => 'Abandonné',
        };
    }

    /** Prospect qui demande encore une action. */
    public function needsAttention(): bool
    {
        return $this === self::Nouveau;
    }
}

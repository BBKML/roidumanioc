<?php

namespace App\Enums;

/** Un simple statut de suivi, pas un module logistique. */
enum CollaborationDeliveryStatus: string
{
    case Prevue = 'prevue';
    case EnCours = 'en_cours';
    case Effectuee = 'effectuee';
    case Receptionnee = 'receptionnee';

    public function label(): string
    {
        return match ($this) {
            self::Prevue => 'Prévue',
            self::EnCours => 'En cours',
            self::Effectuee => 'Effectuée',
            self::Receptionnee => 'Réceptionnée',
        };
    }

    /** @return array<int, self> */
    public static function steps(): array
    {
        return [self::Prevue, self::EnCours, self::Effectuee, self::Receptionnee];
    }
}

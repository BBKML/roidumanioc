<?php

namespace App\Enums;

/**
 * Vocabulaire des types d'activité producteur — reprend les catégories déjà utilisées
 * par MarketplaceListing.type (Récolte / Bouture / Transformé / Intrant), stocké ici
 * via un enum PHP (colonne string) conformément aux conventions du projet.
 */
enum ActivityType: string
{
    case Recolte = 'recolte';
    case Bouture = 'bouture';
    case Transforme = 'transforme';
    case Intrant = 'intrant';

    public function label(): string
    {
        return match ($this) {
            self::Recolte => 'Récolte',
            self::Bouture => 'Bouture',
            self::Transforme => 'Transformé',
            self::Intrant => 'Intrant',
        };
    }
}

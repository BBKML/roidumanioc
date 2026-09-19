<?php

namespace App\Enums;

/**
 * Vocabulaire des types d'activité producteur — reprend à l'origine les catégories déjà
 * utilisées par MarketplaceListing.type (Récolte / Bouture / Transformé / Intrant), stocké
 * ici via un enum PHP (colonne string) conformément aux conventions du projet.
 *
 * `Elevage` ajouté à la demande explicite : la plateforme n'est pas restreinte au manioc
 * ni aux cultures — `CropOffer.product_name`/`BuyerNeed.product_wanted` sont déjà du texte
 * libre sans liste de produits autorisés (un producteur peut déjà taper « Poulet »,
 * « Mouton », « Bœuf »…), seule cette catégorisation de profil ne couvrait pas l'élevage.
 */
enum ActivityType: string
{
    case Recolte = 'recolte';
    case Bouture = 'bouture';
    case Transforme = 'transforme';
    case Intrant = 'intrant';
    case Elevage = 'elevage';

    public function label(): string
    {
        return match ($this) {
            self::Recolte => 'Récolte',
            self::Bouture => 'Bouture',
            self::Transforme => 'Transformé',
            self::Intrant => 'Intrant',
            self::Elevage => 'Élevage',
        };
    }
}

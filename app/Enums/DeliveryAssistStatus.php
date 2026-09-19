<?php

namespace App\Enums;

/**
 * Sous-statut de l'aide à la livraison gérée par l'administration (App\Models\DeliveryAssist,
 * §10). Découplé du statut global CropOrderStatus (4 valeurs seulement côté livraison) —
 * même principe que CollaborationDeliveryStatus vis-à-vis de CollaborationStatus.
 */
enum DeliveryAssistStatus: string
{
    case DemandeAide = 'demande_aide';
    case EnPreparation = 'en_preparation';
    case LivreurContacte = 'livreur_contacte';
    case EnCours = 'en_cours';
    case Livree = 'livree';
    case Annulee = 'annulee';

    public function label(): string
    {
        return match ($this) {
            self::DemandeAide => "Demande d'aide",
            self::EnPreparation => 'Livraison en préparation',
            self::LivreurContacte => 'Livreur contacté',
            self::EnCours => 'Livraison en cours',
            self::Livree => 'Livrée',
            self::Annulee => 'Annulée',
        };
    }

    /** @return array<int, self> Étapes non-annulées, dans l'ordre — mini-stepper admin. */
    public static function steps(): array
    {
        return [self::DemandeAide, self::EnPreparation, self::LivreurContacte, self::EnCours, self::Livree];
    }
}

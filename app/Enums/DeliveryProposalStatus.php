<?php

namespace App\Enums;

/**
 * Statut d'UNE ligne de l'historique de négociation des frais de livraison
 * (crop_order_delivery_proposals). Chaque proposition garde son propre statut, jamais
 * remplacé — une nouvelle contre-proposition fait juste passer l'ancienne « en_attente »
 * à « perimee » (voir CropOrder::proposeDeliveryFee()).
 */
enum DeliveryProposalStatus: string
{
    case EnAttente = 'en_attente';
    case Acceptee = 'acceptee';
    case Perimee = 'perimee';

    public function label(): string
    {
        return match ($this) {
            self::EnAttente => 'En attente',
            self::Acceptee => 'Acceptée',
            self::Perimee => 'Périmée',
        };
    }
}

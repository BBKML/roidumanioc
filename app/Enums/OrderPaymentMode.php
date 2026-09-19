<?php

namespace App\Enums;

enum OrderPaymentMode: string
{
    case Online = 'online';          // payé en ligne, vérifié avant préparation
    case OnDelivery = 'on_delivery';  // payé à la réception (boutique officielle)
    case Direct = 'direct';           // réglé directement avec le producteur (annonce marketplace)

    public function label(): string
    {
        return match ($this) {
            self::Online => 'Payé en ligne',
            self::OnDelivery => 'Paiement à la livraison',
            self::Direct => 'À régler avec le vendeur',
        };
    }
}

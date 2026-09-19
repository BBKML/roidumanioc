<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Paiement = 'paiement';   // en attente du paiement en ligne
    case Validee = 'validee';     // confirmée — à préparer
    case Expediee = 'expediee';   // en cours de livraison
    case Livree = 'livree';       // livrée (et encaissée si paiement à la livraison)
    case Refuse = 'refuse';       // annulée / refusée

    public function label(): string
    {
        return match ($this) {
            self::Paiement => 'Paiement en attente',
            self::Validee => 'À préparer',
            self::Expediee => 'En livraison',
            self::Livree => 'Livrée',
            self::Refuse => 'Annulée',
        };
    }

    public function isOpen(): bool
    {
        return ! in_array($this, [self::Livree, self::Refuse], true);
    }
}

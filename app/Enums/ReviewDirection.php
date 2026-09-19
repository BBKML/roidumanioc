<?php

namespace App\Enums;

enum ReviewDirection: string
{
    case AcheteurVersProducteur = 'acheteur_vers_producteur';
    case ProducteurVersAcheteur = 'producteur_vers_acheteur';

    public function label(): string
    {
        return match ($this) {
            self::AcheteurVersProducteur => "De l'acheteur vers le producteur",
            self::ProducteurVersAcheteur => "Du producteur vers l'acheteur",
        };
    }

    /**
     * Clés de critères attendues dans `reviews.criteria`, selon §21 — différentes selon
     * qui note qui (un acheteur juge la marchandise reçue, un producteur juge le
     * comportement de paiement de l'acheteur).
     *
     * @return array<string, string> clé => libellé
     */
    public function criteria(): array
    {
        return match ($this) {
            self::AcheteurVersProducteur => [
                'qualite' => 'Qualité',
                'quantite' => 'Quantité',
                'respect_engagements' => 'Respect des engagements',
                'ponctualite' => 'Ponctualité',
                'communication' => 'Communication',
            ],
            self::ProducteurVersAcheteur => [
                'respect_engagements' => 'Respect des engagements',
                'paiement' => 'Paiement',
                'communication' => 'Communication',
                'ponctualite' => 'Ponctualité',
            ],
        };
    }
}

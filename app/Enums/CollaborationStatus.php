<?php

namespace App\Enums;

/**
 * Cycle de vie d'une collaboration, ouverte automatiquement dès qu'une
 * ConnectionRequest atteint collaboration_confirmee (App\Livewire\Connect\Show).
 *
 * Chemin nominal (happyPath()) : en_cours -> paiement_declare -> paiement_confirme ->
 * livraison_en_cours -> livraison_confirmee -> terminee. En pratique, le dernier maillon
 * (receptionnee du sous-statut de livraison) fait passer directement à `terminee` — voir
 * Collaboration::markDeliveryStep() — `livraison_confirmee` n'est donc jamais une valeur
 * persistée, seulement une étape du chemin nominal affichée « acquise » par le stepper
 * dès que `terminee` est atteint (même logique que ConnectionRequestStatus::happyPath()).
 *
 * `litige` (déclenché uniquement par l'admin, ou automatiquement si un paiement est
 * contesté — voir contestPayment()) et `annulee` (par l'une des parties, `en_cours`
 * uniquement) gèlent les transitions normales.
 */
enum CollaborationStatus: string
{
    case EnCours = 'en_cours';
    case PaiementDeclare = 'paiement_declare';
    case PaiementConfirme = 'paiement_confirme';
    case LivraisonEnCours = 'livraison_en_cours';
    case LivraisonConfirmee = 'livraison_confirmee';
    case Terminee = 'terminee';
    case Litige = 'litige';
    case Annulee = 'annulee';

    public function label(): string
    {
        return match ($this) {
            self::EnCours => 'En cours',
            self::PaiementDeclare => 'Paiement déclaré',
            self::PaiementConfirme => 'Paiement confirmé',
            self::LivraisonEnCours => 'Livraison en cours',
            self::LivraisonConfirmee => 'Livraison confirmée',
            self::Terminee => 'Terminée',
            self::Litige => 'Litige',
            self::Annulee => 'Annulée',
        };
    }

    /** @return array<int, self> */
    public static function happyPath(): array
    {
        return [
            self::EnCours, self::PaiementDeclare, self::PaiementConfirme,
            self::LivraisonEnCours, self::LivraisonConfirmee, self::Terminee,
        ];
    }
}

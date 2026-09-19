<?php

namespace App\Enums;

/**
 * Machine à états du parcours de commande producteur↔acheteur (voir App\Models\CropOrder).
 * Chemin nominal : EnAttenteProducteur -> Acceptee -> EnAttenteValidationAdmin ->
 * NegociationLivraison -> AccordFinal -> CommandeConfirmee -> AideLivraison ->
 * LivraisonEnPreparation -> LivraisonEnCours -> Livree.
 *
 * `AccordFinal` n'est JAMAIS persisté en base (voir CropOrder::acceptDeliveryFee(), qui
 * saute directement de NegociationLivraison à CommandeConfirmee) — même précédent que
 * CollaborationStatus::LivraisonConfirmee : il n'existe que pour que le stepper affiche
 * cette étape « acquise » dès que CommandeConfirmee est atteint.
 *
 * `EnAttenteValidationAdmin` : les conditions de livraison soumises par le producteur ne
 * sont JAMAIS visibles de l'acheteur tant que l'admin ne les a pas validées (§ demande
 * explicite — admin et producteur en discutent avant que l'acheteur ne voie quoi que ce
 * soit). L'admin peut valider (→ NegociationLivraison, la proposition part réellement à
 * l'acheteur) ou renvoyer au producteur (→ retour à Acceptee, pour resoumission).
 *
 * `LivraisonAutoOrganisee` : alternative à AideLivraison/LivraisonEnPreparation/
 * LivraisonEnCours — le client vient récupérer lui-même (ou a son propre livreur), ou le
 * producteur livre lui-même, SANS jamais passer par l'aide de l'administration.
 * Volontairement absent de happyPath() (comme Refusee/Annulee) : c'est une branche
 * alternative, pas une étape du chemin assisté par l'admin.
 *
 * Refusee (refus initial du producteur, depuis EnAttenteProducteur uniquement) et Annulee
 * (abandon après acceptation, par l'une ou l'autre partie) sont deux issues terminales
 * négatives distinctes — voir CropOrder::refuse()/cancel().
 */
enum CropOrderStatus: string
{
    case EnAttenteProducteur = 'en_attente_producteur';
    case Acceptee = 'acceptee';
    case EnAttenteValidationAdmin = 'en_attente_validation_admin';
    case NegociationLivraison = 'negociation_livraison';
    case AccordFinal = 'accord_final';
    case CommandeConfirmee = 'commande_confirmee';
    case AideLivraison = 'aide_livraison';
    case LivraisonEnPreparation = 'livraison_en_preparation';
    case LivraisonEnCours = 'livraison_en_cours';
    case LivraisonAutoOrganisee = 'livraison_auto_organisee';
    case Livree = 'livree';
    case Refusee = 'refusee';
    case Annulee = 'annulee';

    public function label(): string
    {
        return match ($this) {
            self::EnAttenteProducteur => 'En attente du producteur',
            self::Acceptee => 'Acceptée',
            self::EnAttenteValidationAdmin => 'En attente de validation admin',
            self::NegociationLivraison => 'Négociation de la livraison',
            self::AccordFinal => 'Accord final',
            self::CommandeConfirmee => 'Commande confirmée',
            self::AideLivraison => 'Aide livraison demandée',
            self::LivraisonEnPreparation => 'Livraison en préparation',
            self::LivraisonEnCours => 'Livraison en cours',
            self::LivraisonAutoOrganisee => 'Livraison auto-organisée',
            self::Livree => 'Livrée',
            self::Refusee => 'Refusée',
            self::Annulee => 'Annulée',
        };
    }

    /** Étapes du chemin nominal « assisté », dans l'ordre — sert au stepper visuel. */
    public static function happyPath(): array
    {
        return [
            self::EnAttenteProducteur, self::Acceptee, self::EnAttenteValidationAdmin,
            self::NegociationLivraison, self::AccordFinal, self::CommandeConfirmee,
            self::AideLivraison, self::LivraisonEnPreparation, self::LivraisonEnCours, self::Livree,
        ];
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Livree, self::Refusee, self::Annulee], true);
    }
}

<?php

namespace App\Enums;

/**
 * Machine à états de la mise en relation (cahier des charges §14). Chemin nominal :
 * EnAttente -> Acceptee -> Negociation -> Proposition -> CollaborationConfirmee.
 * Refusee (déclin par l'une des parties) et Annulee (retrait par le demandeur, possible
 * uniquement depuis EnAttente) sont deux issues terminales négatives, atteignables
 * depuis plusieurs étapes du chemin nominal — voir App\Models\ConnectionRequest.
 */
enum ConnectionRequestStatus: string
{
    case EnAttente = 'en_attente';
    case Acceptee = 'acceptee';
    case Refusee = 'refusee';
    case Negociation = 'negociation';
    case Proposition = 'proposition';
    case CollaborationConfirmee = 'collaboration_confirmee';
    case Annulee = 'annulee';

    public function label(): string
    {
        return match ($this) {
            self::EnAttente => 'En attente',
            self::Acceptee => 'Acceptée',
            self::Refusee => 'Refusée',
            self::Negociation => 'Négociation',
            self::Proposition => 'Proposition',
            self::CollaborationConfirmee => 'Collaboration confirmée',
            self::Annulee => 'Annulée',
        };
    }

    /** Étapes du chemin nominal, dans l'ordre — sert au stepper visuel. */
    public static function happyPath(): array
    {
        return [self::EnAttente, self::Acceptee, self::Negociation, self::Proposition, self::CollaborationConfirmee];
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Refusee, self::Annulee, self::CollaborationConfirmee], true);
    }
}

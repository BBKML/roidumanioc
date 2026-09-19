<?php

namespace App\Actions;

use App\Enums\ConnectionRequestStatus;
use App\Events\ConnectionRequestCreated;
use App\Models\BuyerNeed;
use App\Models\ConnectionRequest;
use App\Models\CropOffer;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Point d'entrée unique pour créer une demande de mise en relation (offre ou besoin) —
 * même esprit que App\Actions\DeclarePayment.
 *
 * Sécurité :
 * - compte suspendu bloqué ;
 * - rate limit par utilisateur ;
 * - pas deux demandes OUVERTES entre le même producteur et le même acheteur sur la même
 *   offre/le même besoin (une fois refusée/annulée, une nouvelle demande redevient possible).
 */
class CreateConnectionRequest
{
    /** Statuts considérés « ouverts » pour la détection de doublon. */
    private const OPEN_STATUSES = [
        ConnectionRequestStatus::EnAttente,
        ConnectionRequestStatus::Acceptee,
        ConnectionRequestStatus::Negociation,
        ConnectionRequestStatus::Proposition,
    ];

    public function handle(
        User $requester,
        string $requesterRole,
        CropOffer|BuyerNeed $target,
        ?string $message = null,
        ?float $quantity = null,
        ?int $priceTotal = null,
    ): ConnectionRequest {
        abort_unless($requester->isActive(), 403, 'Compte inactif.');

        $key = 'connection-request:'.$requester->id;
        if (RateLimiter::tooManyAttempts($key, 10)) {
            // Clé 'general' (pas 'message') : ce refus n'a rien à voir avec le texte tapé
            // dans le champ message — l'afficher dessous laisserait croire le contraire
            // (cf. audit UX, corrigé en Connect\RequestOffer/RequestNeed).
            throw ValidationException::withMessages([
                'general' => 'Trop de demandes envoyées. Réessayez plus tard.',
            ]);
        }
        RateLimiter::hit($key, 3600);

        if ($requesterRole === 'acheteur') {
            abort_unless($target instanceof CropOffer, 404);
            $buyerProfile = $requester->buyerProfile;
            abort_unless($buyerProfile, 403);
            $producerProfile = $target->producerProfile;
            $cropOfferId = $target->id;
            $buyerNeedId = null;
        } else {
            abort_unless($target instanceof BuyerNeed, 404);
            $producerProfile = $requester->producerProfile;
            abort_unless($producerProfile, 403);
            $buyerProfile = $target->buyerProfile;
            $cropOfferId = null;
            $buyerNeedId = $target->id;
        }

        $this->guardAgainstDuplicate($producerProfile->id, $buyerProfile->id, $cropOfferId, $buyerNeedId);

        $connectionRequest = ConnectionRequest::create([
            'requester_user_id' => $requester->id,
            'requester_role' => $requesterRole,
            'crop_offer_id' => $cropOfferId,
            'buyer_need_id' => $buyerNeedId,
            'producer_profile_id' => $producerProfile->id,
            'buyer_profile_id' => $buyerProfile->id,
            'message_initial' => $message,
            // Capturé dès le premier contact (le demandeur connaît déjà la quantité/le prix
            // de l'offre/du besoin) — transformé en vraie proposition dès que l'autre partie
            // accepte, cf. Connect\Show::accept() (le statut `proposition` n'est atteignable
            // qu'après `negociation`, donc impossible de proposer réellement ici).
            'initial_proposal_terms' => $quantity !== null ? [
                'quantity' => $quantity,
                'unit' => $target->unit->value,
                'price_total' => $priceTotal,
            ] : null,
        ]);

        event(new ConnectionRequestCreated($connectionRequest));

        // Le message tapé à l'amorce ("message_initial", repris tel quel plus tard comme
        // terms_note de la Collaboration — jamais modifié) doit aussi apparaître comme la
        // toute première bulle du fil de discussion : sans ça, l'autre partie ouvre une
        // conversation qui semble vide alors qu'elle vient pourtant d'accepter/négocier sur
        // la base de ce texte (confusion signalée en usage réel). Passe par la même action
        // que n'importe quel autre message (détection de coordonnées §16 comprise) ; un
        // échec ici (rate limit atteint juste avant) ne doit jamais faire échouer la
        // création de la demande elle-même, qui est l'action principale.
        if ($message !== null) {
            try {
                // notify: false — "nouvelle demande" (déjà déclenchée ci-dessus) est LA
                // notification de cette action ; une seconde notification "nouveau message"
                // pour ce même geste serait redondante (§24, sans sur-notifier).
                app(SendConversationMessage::class)->handle($requester, $connectionRequest, $message, notify: false);
            } catch (ValidationException) {
                // Silencieux : message_initial reste de toute façon enregistré sur la demande.
            }
        }

        return $connectionRequest;
    }

    private function guardAgainstDuplicate(int $producerProfileId, int $buyerProfileId, ?int $cropOfferId, ?int $buyerNeedId): void
    {
        $exists = ConnectionRequest::query()
            ->where('producer_profile_id', $producerProfileId)
            ->where('buyer_profile_id', $buyerProfileId)
            ->when($cropOfferId, fn ($q, $id) => $q->where('crop_offer_id', $id))
            ->when($buyerNeedId, fn ($q, $id) => $q->where('buyer_need_id', $id))
            ->whereIn('status', self::OPEN_STATUSES)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'general' => 'Une demande est déjà en cours entre vous pour cette offre/ce besoin.',
            ]);
        }
    }
}

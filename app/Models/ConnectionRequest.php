<?php

namespace App\Models;

use App\Enums\ConnectionRequestStatus;
use App\Events\ConnectionRequestAccepted;
use App\Events\ConnectionRequestCancelled;
use App\Events\ConnectionRequestConfirmed;
use App\Events\ConnectionRequestMovedToNegotiation;
use App\Events\ConnectionRequestProposed;
use App\Events\ConnectionRequestRefused;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Mise en relation entre un producteur et un acheteur (cahier des charges §14).
 *
 * Machine à états — voir App\Enums\ConnectionRequestStatus::happyPath() pour le chemin
 * nominal. Deux issues négatives, chacune avec sa propre sémantique :
 *  - refuse() : l'UNE des deux parties décline/met fin — possible depuis n'importe quelle
 *    étape non terminale. Depuis `en_attente`, seul le destinataire peut refuser (le
 *    demandeur qui change d'avis doit passer par cancel(), pas refuse() : ce n'est pas la
 *    même intention et les deux ne doivent pas se confondre dans l'historique).
 *  - cancel() : le DEMANDEUR retire sa propre demande avant toute réponse — possible
 *    uniquement depuis `en_attente` (au-delà, l'autre partie est déjà engagée : c'est à
 *    elle de refuser si besoin, pas au demandeur d'annuler unilatéralement).
 *
 * Chaque transition est gardée par un prédicat `canXxxBy(User $actor)` réutilisé à la
 * fois par ConnectionRequestPolicy (couche UI) et par la méthode de transition elle-même
 * (garde-fou en profondeur, même si le composant Livewire a déjà vérifié la policy) —
 * même esprit que Payment::confirm()/reject() : idempotent, renvoie false plutôt que de
 * lever une exception si l'état ou l'acteur ne correspond pas.
 */
class ConnectionRequest extends Model
{
    use LogsActivity;

    protected $fillable = [
        'requester_user_id', 'requester_role', 'crop_offer_id', 'buyer_need_id',
        'producer_profile_id', 'buyer_profile_id', 'status', 'message_initial',
        'initial_proposal_terms',
    ];

    protected function casts(): array
    {
        return [
            'status' => ConnectionRequestStatus::class,
            'initial_proposal_terms' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $cr) {
            $hasOffer = filled($cr->crop_offer_id);
            $hasNeed = filled($cr->buyer_need_id);

            if ($hasOffer === $hasNeed) {
                throw new \InvalidArgumentException(
                    'Une demande de mise en relation doit porter sur exactement une offre OU un besoin.'
                );
            }

            $cr->status ??= ConnectionRequestStatus::EnAttente;
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('connection_request');
    }

    /* ----------------------------------------------------------------
     |  Relations
     |---------------------------------------------------------------- */

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_user_id');
    }

    public function cropOffer(): BelongsTo
    {
        return $this->belongsTo(CropOffer::class);
    }

    public function buyerNeed(): BelongsTo
    {
        return $this->belongsTo(BuyerNeed::class);
    }

    public function producerProfile(): BelongsTo
    {
        return $this->belongsTo(ProducerProfile::class);
    }

    public function buyerProfile(): BelongsTo
    {
        return $this->belongsTo(BuyerProfile::class);
    }

    public function conversation(): HasOne
    {
        return $this->hasOne(Conversation::class);
    }

    public function collaboration(): HasOne
    {
        return $this->hasOne(Collaboration::class);
    }

    /* ----------------------------------------------------------------
     |  Parties
     |---------------------------------------------------------------- */

    public function isRequester(User $user): bool
    {
        return $this->requester_user_id === $user->id;
    }

    public function isParty(User $user): bool
    {
        return $user->id === $this->producerProfile->user_id || $user->id === $this->buyerProfile->user_id;
    }

    /** Le destinataire = l'autre partie que le demandeur. */
    public function isReceiver(User $user): bool
    {
        return $this->isParty($user) && ! $this->isRequester($user);
    }

    /** L'instance User du destinataire — pour les notifications (Phase 9). */
    public function receiverUser(): User
    {
        return $this->requester_role === 'acheteur' ? $this->producerProfile->user : $this->buyerProfile->user;
    }

    /** Libellé du produit concerné (offre OU besoin) — pour les notifications (Phase 9). */
    public function productLabel(): string
    {
        return $this->cropOffer?->product_name ?? $this->buyerNeed?->product_wanted ?? 'ce produit';
    }

    /**
     * L'autre partie que `$actor` — pour notifier celui qui n'a pas agi (Phase 9).
     * Utile pour propose()/confirmCollaboration() (accessibles aux deux parties) et pour
     * les messages de conversation.
     */
    public function otherParty(User $actor): User
    {
        return $this->isRequester($actor) ? $this->receiverUser() : $this->requester;
    }

    /* ----------------------------------------------------------------
     |  Prédicats de garde (policy + modèle)
     |---------------------------------------------------------------- */

    public function canBeAcceptedBy(User $user): bool
    {
        return $this->status === ConnectionRequestStatus::EnAttente && $this->isReceiver($user);
    }

    public function canBeCancelledBy(User $user): bool
    {
        return $this->status === ConnectionRequestStatus::EnAttente && $this->isRequester($user);
    }

    public function canBeRefusedBy(User $user): bool
    {
        return match ($this->status) {
            ConnectionRequestStatus::EnAttente => $this->isReceiver($user),
            ConnectionRequestStatus::Acceptee,
            ConnectionRequestStatus::Negociation,
            ConnectionRequestStatus::Proposition => $this->isParty($user),
            default => false,
        };
    }

    public function canMoveToNegotiationBy(User $user): bool
    {
        return $this->status === ConnectionRequestStatus::Acceptee && $this->isParty($user);
    }

    public function canProposeBy(User $user): bool
    {
        return $this->status === ConnectionRequestStatus::Negociation && $this->isParty($user);
    }

    public function canConfirmCollaborationBy(User $user): bool
    {
        if ($this->status !== ConnectionRequestStatus::Proposition || ! $this->isParty($user)) {
            return false;
        }

        // On ne peut jamais accepter sa propre proposition — seule l'autre partie que
        // celle qui a envoyé la dernière proposition peut confirmer la collaboration
        // (bug remonté en usage réel : le bouton « Accepter » apparaissait aussi chez
        // l'auteur de la proposition, sur sa propre carte dans le fil).
        return $this->latestProposalSenderId() !== $user->id;
    }

    /** Dernier message de type « proposition » du fil — null s'il n'y en a aucun. */
    public function latestProposal(): ?ConversationMessage
    {
        return $this->conversation
            ?->messages()
            ->where('type', 'proposition')
            ->latest()
            ->first();
    }

    /** Auteur du dernier message de type « proposition » du fil — null s'il n'y en a aucun. */
    public function latestProposalSenderId(): ?int
    {
        return $this->latestProposal()?->sender_id;
    }

    /* ----------------------------------------------------------------
     |  Transitions
     |---------------------------------------------------------------- */

    public function accept(User $actor): bool
    {
        if (! $this->canBeAcceptedBy($actor)) {
            return false;
        }

        $this->update(['status' => ConnectionRequestStatus::Acceptee]);
        event(new ConnectionRequestAccepted($this));

        return true;
    }

    public function cancel(User $actor): bool
    {
        if (! $this->canBeCancelledBy($actor)) {
            return false;
        }

        $this->update(['status' => ConnectionRequestStatus::Annulee]);
        event(new ConnectionRequestCancelled($this));

        return true;
    }

    public function refuse(User $actor): bool
    {
        if (! $this->canBeRefusedBy($actor)) {
            return false;
        }

        $this->update(['status' => ConnectionRequestStatus::Refusee]);
        event(new ConnectionRequestRefused($this));

        return true;
    }

    public function moveToNegotiation(User $actor): bool
    {
        if (! $this->canMoveToNegotiationBy($actor)) {
            return false;
        }

        $this->update(['status' => ConnectionRequestStatus::Negociation]);
        event(new ConnectionRequestMovedToNegotiation($this));

        return true;
    }

    public function propose(User $actor): bool
    {
        if (! $this->canProposeBy($actor)) {
            return false;
        }

        $this->update(['status' => ConnectionRequestStatus::Proposition]);
        event(new ConnectionRequestProposed($this, $actor));

        return true;
    }

    public function confirmCollaboration(User $actor): bool
    {
        if (! $this->canConfirmCollaborationBy($actor)) {
            return false;
        }

        DB::transaction(function () {
            $this->update(['status' => ConnectionRequestStatus::CollaborationConfirmee]);
            $this->createCollaborationAgreement();
        });

        event(new ConnectionRequestConfirmed($this, $actor));

        return true;
    }

    /**
     * Formalise l'accord (§18) dès que la transition réussit — product repris de l'offre
     * ou du besoin d'origine (son intitulé ne change pas pendant la négociation),
     * quantity/unit/price_total repris de la DERNIÈRE proposition structurée (ce qui a été
     * réellement négocié dans le chat, pas la demande initiale — corrigé après un constat
     * en usage réel : `agreed_price_total` restait toujours `null` et la quantité
     * n'était jamais celle négociée). Le statut `proposition` n'est atteignable qu'après
     * l'envoi d'une proposition (cf. propose()), donc `latestProposal()` existe toujours
     * ici en pratique ; repli sur l'offre/besoin d'origine en garde défensive seulement.
     * `terms_note` reste le message initial (contexte de la demande, pas les termes
     * chiffrés — ceux-ci sont dans les champs dédiés ci-dessous). Rien n'est modifiable
     * ensuite dans cette V1 (la négociation reste dans la messagerie). Idempotent
     * (`firstOrCreate`) : un double clic ne duplique jamais la collaboration. Vit sur le
     * modèle plutôt que dans un Livewire component (déplacé depuis Connect\Show, Phase 14)
     * pour que N'IMPORTE QUEL appelant de confirmCollaboration() (l'écran principal, la
     * carte de proposition dans le chat, un futur point d'entrée) obtienne le même
     * comportement automatique — cf. § « Création automatique, pas manuelle » dans CLAUDE.md.
     */
    private function createCollaborationAgreement(): void
    {
        $target = $this->crop_offer_id ? $this->cropOffer : $this->buyerNeed;
        $terms = $this->latestProposal()?->proposal_terms ?? [];

        $collaboration = Collaboration::firstOrCreate(
            ['connection_request_id' => $this->id],
            [
                'producer_profile_id' => $this->producer_profile_id,
                'buyer_profile_id' => $this->buyer_profile_id,
                'agreed_product' => $this->crop_offer_id ? $target->product_name : $target->product_wanted,
                'agreed_quantity' => $terms['quantity'] ?? $target->quantity,
                'agreed_unit' => $terms['unit'] ?? $target->unit->value,
                'agreed_price_total' => $terms['price_total'] ?? null,
                'terms_note' => $this->message_initial,
                'started_at' => now(),
            ],
        );

        $collaboration->delivery()->firstOrCreate([]);
    }

    /**
     * Position (0-indexée) atteinte dans le chemin nominal — pour le stepper visuel.
     * Si le statut courant fait partie du chemin nominal, c'est simplement sa position.
     * Sinon (refusée/annulée), déduite de la dernière entrée de l'historique
     * `LogsActivity` (`properties.old.status` = le statut juste avant la transition qui
     * a mis fin à la demande) — fiable ici car refuse()/cancel() sont les seuls points
     * d'entrée qui posent ces deux statuts, donc la dernière entrée est toujours la leur.
     * Renvoie null si aucune étape du chemin nominal n'a jamais été atteinte.
     */
    public function furthestHappyPathIndex(): ?int
    {
        $happyValues = array_map(fn (ConnectionRequestStatus $s) => $s->value, ConnectionRequestStatus::happyPath());

        $index = array_search($this->status->value, $happyValues, true);
        if ($index !== false) {
            return $index;
        }

        $previous = $this->activities()->latest()->first()?->properties['old']['status'] ?? null;
        $index = $previous ? array_search($previous, $happyValues, true) : false;

        return $index === false ? null : $index;
    }
}

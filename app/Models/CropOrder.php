<?php

namespace App\Models;

use App\Enums\CropOrderStatus;
use App\Enums\DeliveryAssistStatus;
use App\Enums\DeliveryProposalStatus;
use App\Events\CropOrderAccepted;
use App\Events\CropOrderCancelled;
use App\Events\CropOrderConfirmed;
use App\Events\CropOrderDelivered;
use App\Events\CropOrderDeliveryAssistanceRequested;
use App\Events\CropOrderDeliveryAssistStepMarked;
use App\Events\CropOrderDeliveryConditionsRejected;
use App\Events\CropOrderDeliveryConditionsSubmittedForReview;
use App\Events\CropOrderDeliveryFeeProposed;
use App\Events\CropOrderRefused;
use App\Events\CropOrderSelfArrangedDeliveryDeclared;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Parcours de commande structuré producteur↔acheteur, façon Alibaba — délibérément SÉPARÉ
 * du système de mise en relation par chat (ConnectionRequest/Conversation/Collaboration,
 * §14), qui reste intact. Ici, pas de discussion libre : un objet métier avec des statuts
 * explicites à chaque étape.
 *
 * Chemin nominal : cf. App\Enums\CropOrderStatus::happyPath(). Mêmes conventions que
 * ConnectionRequest/Collaboration : chaque transition est gardée par un prédicat
 * `canXxxBy()` réutilisé par CropOrderPolicy ET par la méthode elle-même (défense en
 * profondeur), chaque transition est idempotente (renvoie false plutôt que lever une
 * exception), aucun contournement admin sur les actions réservées aux parties (voir
 * AppServiceProvider — seul markDeliveryAssistStep() reste un pouvoir admin).
 */
class CropOrder extends Model
{
    use LogsActivity;

    protected $fillable = [
        'crop_offer_id', 'producer_profile_id', 'buyer_profile_id',
        'product_name', 'variety', 'requested_quantity', 'requested_unit',
        'delivery_location', 'desired_date', 'delivery_notes', 'payment_method',
        'quality_expected', 'buyer_message', 'status', 'refusal_reason',
        'product_price_total', 'delivery_conditions_note', 'delivery_fee_agreed',
        'total_amount', 'accepted_at', 'confirmed_at', 'delivery_assist_requested_at',
        'delivered_at', 'cancelled_at', 'pending_product_price_total', 'pending_delivery_fee',
        'admin_review_note', 'self_arranged_mode', 'self_arranged_by', 'self_arranged_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_quantity' => 'decimal:2',
            'desired_date' => 'date',
            'status' => CropOrderStatus::class,
            'product_price_total' => 'integer',
            'delivery_fee_agreed' => 'integer',
            'total_amount' => 'integer',
            'accepted_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'delivery_assist_requested_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'pending_product_price_total' => 'integer',
            'pending_delivery_fee' => 'integer',
            'self_arranged_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('crop_order');
    }

    /* ----------------------------------------------------------------
     |  Relations
     |---------------------------------------------------------------- */

    public function cropOffer(): BelongsTo
    {
        return $this->belongsTo(CropOffer::class);
    }

    public function producerProfile(): BelongsTo
    {
        return $this->belongsTo(ProducerProfile::class);
    }

    public function buyerProfile(): BelongsTo
    {
        return $this->belongsTo(BuyerProfile::class);
    }

    public function deliveryProposals(): HasMany
    {
        return $this->hasMany(CropOrderDeliveryProposal::class);
    }

    public function deliveryAssist(): HasOne
    {
        return $this->hasOne(DeliveryAssist::class);
    }

    public function selfArrangedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'self_arranged_by');
    }

    /* ----------------------------------------------------------------
     |  Parties
     |---------------------------------------------------------------- */

    public function isProducer(User $user): bool
    {
        return $user->id === $this->producerProfile->user_id;
    }

    public function isBuyer(User $user): bool
    {
        return $user->id === $this->buyerProfile->user_id;
    }

    public function isParty(User $user): bool
    {
        return $this->isProducer($user) || $this->isBuyer($user);
    }

    /** L'autre partie que `$actor` — pour notifier celui qui n'a pas agi. */
    public function otherParty(User $actor): User
    {
        return $this->isProducer($actor) ? $this->buyerProfile->user : $this->producerProfile->user;
    }

    /* ----------------------------------------------------------------
     |  Négociation des frais de livraison
     |---------------------------------------------------------------- */

    /**
     * Dernière ligne de l'historique — null s'il n'y en a aucune. Trié par `id` (pas
     * `created_at`, dont la granularité à la seconde peut faire égalité entre deux
     * propositions rapprochées) pour garantir l'ordre d'insertion réel.
     */
    public function latestDeliveryProposal(): ?CropOrderDeliveryProposal
    {
        return $this->deliveryProposals()->latest('id')->first();
    }

    public function latestDeliveryProposalAuthorId(): ?int
    {
        return $this->latestDeliveryProposal()?->proposed_by;
    }

    /* ----------------------------------------------------------------
     |  Prédicats de garde (policy + modèle)
     |---------------------------------------------------------------- */

    public function canBeAcceptedBy(User $user): bool
    {
        return $this->status === CropOrderStatus::EnAttenteProducteur && $this->isProducer($user);
    }

    public function canBeRefusedBy(User $user): bool
    {
        return $this->status === CropOrderStatus::EnAttenteProducteur && $this->isProducer($user);
    }

    public function canBeCancelledBy(User $user): bool
    {
        return match ($this->status) {
            CropOrderStatus::EnAttenteProducteur => $this->isBuyer($user),
            CropOrderStatus::Acceptee, CropOrderStatus::NegociationLivraison => $this->isParty($user),
            default => false,
        };
    }

    /** Le producteur soumet ses conditions à l'admin — pas encore visibles de l'acheteur. */
    public function canSubmitDeliveryConditionsBy(User $user): bool
    {
        return $this->status === CropOrderStatus::Acceptee && $this->isProducer($user);
    }

    /** Valider (→ envoi réel à l'acheteur) ou renvoyer au producteur — pouvoir admin uniquement. */
    public function canReviewDeliveryConditionsBy(User $user): bool
    {
        return $user->isAdmin() && $this->status === CropOrderStatus::EnAttenteValidationAdmin;
    }

    /** Une partie ne peut jamais agir sur sa propre dernière proposition — attendre l'autre. */
    public function canProposeDeliveryFeeBy(User $user): bool
    {
        return $this->status === CropOrderStatus::NegociationLivraison
            && $this->isParty($user)
            && $this->latestDeliveryProposalAuthorId() !== $user->id;
    }

    public function canAcceptDeliveryFeeBy(User $user): bool
    {
        return $this->status === CropOrderStatus::NegociationLivraison
            && $this->isParty($user)
            && $this->latestDeliveryProposalAuthorId() !== $user->id;
    }

    public function canRequestDeliveryAssistanceBy(User $user): bool
    {
        return $this->status === CropOrderStatus::CommandeConfirmee && $this->isParty($user);
    }

    /**
     * Livraison auto-organisée (sans l'admin) — 3ᵉ choix à côté de « Aide livraison ».
     * `$mode = 'acheteur'` : le client récupère lui-même / a son propre livreur, seul
     * l'acheteur peut le déclarer. `$mode = 'producteur'` : le producteur livre lui-même,
     * seul le producteur peut le déclarer — jamais l'autre partie à sa place.
     */
    public function canDeclareSelfArrangedDeliveryBy(User $user, string $mode): bool
    {
        if ($this->status !== CropOrderStatus::CommandeConfirmee) {
            return false;
        }

        return match ($mode) {
            'acheteur' => $this->isBuyer($user),
            'producteur' => $this->isProducer($user),
            default => false,
        };
    }

    /** Seul l'acheteur confirme avoir reçu la marchandise (même logique que CollaborationDeliveryStatus::Receptionnee). */
    public function canConfirmSelfArrangedDeliveryBy(User $user): bool
    {
        return $this->status === CropOrderStatus::LivraisonAutoOrganisee && $this->isBuyer($user);
    }

    /** Revenir en arrière si les plans changent — n'importe quelle partie. */
    public function canCancelSelfArrangedDeliveryBy(User $user): bool
    {
        return $this->status === CropOrderStatus::LivraisonAutoOrganisee && $this->isParty($user);
    }

    public function canMarkDeliveryAssistStepBy(User $user, DeliveryAssistStatus $to): bool
    {
        if (! $user->isAdmin()) {
            return false;
        }

        $current = $this->deliveryAssist?->status ?? DeliveryAssistStatus::DemandeAide;

        return match (true) {
            $to === DeliveryAssistStatus::EnPreparation && $current === DeliveryAssistStatus::DemandeAide => true,
            $to === DeliveryAssistStatus::LivreurContacte && $current === DeliveryAssistStatus::EnPreparation => true,
            $to === DeliveryAssistStatus::EnCours && $current === DeliveryAssistStatus::LivreurContacte => true,
            $to === DeliveryAssistStatus::Livree && $current === DeliveryAssistStatus::EnCours => true,
            $to === DeliveryAssistStatus::Annulee && ! in_array($current, [DeliveryAssistStatus::Livree, DeliveryAssistStatus::Annulee], true) => true,
            default => false,
        };
    }

    /* ----------------------------------------------------------------
     |  Transitions
     |---------------------------------------------------------------- */

    public function accept(User $producer): bool
    {
        if (! $this->canBeAcceptedBy($producer)) {
            return false;
        }

        $this->update(['status' => CropOrderStatus::Acceptee, 'accepted_at' => now()]);
        event(new CropOrderAccepted($this));

        return true;
    }

    public function refuse(User $producer, ?string $reason = null): bool
    {
        if (! $this->canBeRefusedBy($producer)) {
            return false;
        }

        $this->update(['status' => CropOrderStatus::Refusee, 'refusal_reason' => $reason]);
        event(new CropOrderRefused($this));

        return true;
    }

    public function cancel(User $actor): bool
    {
        if (! $this->canBeCancelledBy($actor)) {
            return false;
        }

        $this->update(['status' => CropOrderStatus::Annulee, 'cancelled_at' => now()]);
        event(new CropOrderCancelled($this, $actor));

        return true;
    }

    /**
     * Le producteur fixe le prix TOTAL du produit (jamais renégocié ensuite) et propose un
     * premier montant de frais de livraison — MAIS ces valeurs restent privées (admin +
     * producteur uniquement) tant que l'admin ne les a pas validées (canReviewDeliveryConditionsBy) :
     * ni la proposition de frais ni le prix ne sont visibles de l'acheteur à ce stade.
     */
    public function submitDeliveryConditionsForReview(User $producer, int $productPriceTotal, int $deliveryFeeProposed, ?string $note = null): bool
    {
        if (! $this->canSubmitDeliveryConditionsBy($producer)) {
            return false;
        }

        $this->update([
            'pending_product_price_total' => $productPriceTotal,
            'pending_delivery_fee' => $deliveryFeeProposed,
            'delivery_conditions_note' => $note,
            'status' => CropOrderStatus::EnAttenteValidationAdmin,
        ]);

        event(new CropOrderDeliveryConditionsSubmittedForReview($this));

        return true;
    }

    /**
     * L'admin valide les conditions soumises : elles deviennent réellement visibles de
     * l'acheteur (première ligne de l'historique de négociation, §5/§6) — l'auteur de
     * cette proposition reste le PRODUCTEUR (l'admin ne fait qu'autoriser sa diffusion),
     * donc la garde « pas sur sa propre proposition » (canAcceptDeliveryFeeBy) continue de
     * viser le producteur, pas l'admin.
     */
    public function approveDeliveryConditions(User $admin, ?string $note = null): bool
    {
        if (! $this->canReviewDeliveryConditionsBy($admin)) {
            return false;
        }

        DB::transaction(function () use ($admin, $note) {
            $this->update([
                'product_price_total' => $this->pending_product_price_total,
                'admin_review_note' => $note,
                'pending_product_price_total' => null,
                'status' => CropOrderStatus::NegociationLivraison,
            ]);

            $this->deliveryProposals()->create([
                'proposed_by' => $this->producerProfile->user_id,
                'amount' => $this->pending_delivery_fee,
                'note' => $this->delivery_conditions_note,
                'status' => DeliveryProposalStatus::EnAttente,
            ]);

            $this->update(['pending_delivery_fee' => null]);
        });

        event(new CropOrderDeliveryFeeProposed($this, $this->producerProfile->user, isFirst: true));

        return true;
    }

    /** Renvoie au producteur pour resoumission — jamais de proposition envoyée à l'acheteur. */
    public function rejectDeliveryConditions(User $admin, string $reason): bool
    {
        if (! $this->canReviewDeliveryConditionsBy($admin)) {
            return false;
        }

        $this->update([
            'status' => CropOrderStatus::Acceptee,
            'admin_review_note' => $reason,
            'pending_product_price_total' => null,
            'pending_delivery_fee' => null,
        ]);

        event(new CropOrderDeliveryConditionsRejected($this, $admin, $reason));

        return true;
    }

    /** Contre-proposition — l'ancienne ligne « en_attente » devient « perimee », jamais écrasée. */
    public function proposeDeliveryFee(User $actor, int $amount, ?string $note = null): bool
    {
        if (! $this->canProposeDeliveryFeeBy($actor)) {
            return false;
        }

        DB::transaction(function () use ($actor, $amount, $note) {
            $this->deliveryProposals()
                ->where('status', DeliveryProposalStatus::EnAttente)
                ->update(['status' => DeliveryProposalStatus::Perimee]);

            $this->deliveryProposals()->create([
                'proposed_by' => $actor->id,
                'amount' => $amount,
                'note' => $note,
                'status' => DeliveryProposalStatus::EnAttente,
            ]);
        });

        event(new CropOrderDeliveryFeeProposed($this, $actor, isFirst: false));

        return true;
    }

    /** Accepte la dernière proposition en date — figent les conditions finales (§8). */
    public function acceptDeliveryFee(User $actor): bool
    {
        if (! $this->canAcceptDeliveryFeeBy($actor)) {
            return false;
        }

        DB::transaction(function () use ($actor) {
            $proposal = $this->latestDeliveryProposal();
            $proposal?->update(['status' => DeliveryProposalStatus::Acceptee]);

            $fee = $proposal?->amount ?? 0;
            $total = ($this->product_price_total ?? 0) + $fee;

            $this->update([
                'delivery_fee_agreed' => $fee,
                'total_amount' => $total,
                'status' => CropOrderStatus::CommandeConfirmee,
                'confirmed_at' => now(),
            ]);
        });

        event(new CropOrderConfirmed($this, $actor));

        return true;
    }

    /** Idempotent — un double clic ne duplique jamais la demande d'aide. */
    public function requestDeliveryAssistance(User $actor): bool
    {
        if (! $this->canRequestDeliveryAssistanceBy($actor)) {
            return false;
        }

        DB::transaction(function () use ($actor) {
            $this->deliveryAssist()->firstOrCreate([], [
                'requested_by' => $actor->id,
                'status' => DeliveryAssistStatus::DemandeAide,
                'requested_at' => now(),
            ]);

            $this->update([
                'status' => CropOrderStatus::AideLivraison,
                'delivery_assist_requested_at' => now(),
            ]);
        });

        event(new CropOrderDeliveryAssistanceRequested($this, $actor));

        return true;
    }

    /**
     * Livraison auto-organisée — ni le client ni le producteur n'ont besoin de
     * l'administration (l'un des deux a son propre moyen de transport). Ferme
     * immédiatement toute possibilité de demander l'aide admin en parallèle (statuts
     * mutuellement exclusifs depuis `commande_confirmee`).
     */
    public function declareSelfArrangedDelivery(User $actor, string $mode): bool
    {
        if (! $this->canDeclareSelfArrangedDeliveryBy($actor, $mode)) {
            return false;
        }

        $this->update([
            'status' => CropOrderStatus::LivraisonAutoOrganisee,
            'self_arranged_mode' => $mode,
            'self_arranged_by' => $actor->id,
            'self_arranged_at' => now(),
        ]);

        event(new CropOrderSelfArrangedDeliveryDeclared($this, $actor, $mode));

        return true;
    }

    /** L'acheteur confirme avoir reçu la marchandise — clôt la commande, sans passer par l'admin. */
    public function confirmSelfArrangedDelivery(User $buyer): bool
    {
        if (! $this->canConfirmSelfArrangedDeliveryBy($buyer)) {
            return false;
        }

        $this->update(['status' => CropOrderStatus::Livree, 'delivered_at' => now()]);
        event(new CropOrderDelivered($this, $buyer));

        return true;
    }

    /** Les plans changent — retour à `commande_confirmee`, les 3 choix redeviennent possibles. */
    public function cancelSelfArrangedDelivery(User $actor): bool
    {
        if (! $this->canCancelSelfArrangedDeliveryBy($actor)) {
            return false;
        }

        $this->update([
            'status' => CropOrderStatus::CommandeConfirmee,
            'self_arranged_mode' => null,
            'self_arranged_by' => null,
            'self_arranged_at' => null,
        ]);

        return true;
    }

    /**
     * Sous-transition pilotée par l'admin (§10). Une sous-étape « annulée » ramène le
     * statut global à `commande_confirmee` — c'est l'AIDE qui est annulée, pas la commande.
     */
    public function markDeliveryAssistStep(User $admin, DeliveryAssistStatus $to): bool
    {
        if (! $this->canMarkDeliveryAssistStepBy($admin, $to)) {
            return false;
        }

        DB::transaction(function () use ($admin, $to) {
            $this->deliveryAssist?->update([
                'status' => $to,
                'updated_by' => $admin->id,
                'delivered_at' => $to === DeliveryAssistStatus::Livree ? now() : $this->deliveryAssist->delivered_at,
                'cancelled_at' => $to === DeliveryAssistStatus::Annulee ? now() : $this->deliveryAssist->cancelled_at,
            ]);

            $newStatus = match ($to) {
                DeliveryAssistStatus::EnPreparation, DeliveryAssistStatus::LivreurContacte => CropOrderStatus::LivraisonEnPreparation,
                DeliveryAssistStatus::EnCours => CropOrderStatus::LivraisonEnCours,
                DeliveryAssistStatus::Livree => CropOrderStatus::Livree,
                DeliveryAssistStatus::Annulee => CropOrderStatus::CommandeConfirmee,
                default => $this->status,
            };

            $this->update([
                'status' => $newStatus,
                'delivered_at' => $to === DeliveryAssistStatus::Livree ? now() : $this->delivered_at,
            ]);
        });

        event(new CropOrderDeliveryAssistStepMarked($this, $admin, $to));

        return true;
    }

    /**
     * Position (0-indexée) atteinte dans le chemin nominal — pour le stepper visuel. Même
     * logique que ConnectionRequest::furthestHappyPathIndex() (repli sur la dernière entrée
     * de l'historique LogsActivity pour les statuts terminaux négatifs). `$happyPath`
     * optionnel : permet au composant Livewire de passer le chemin FILTRÉ (livraison
     * auto-organisée, cf. Show::render()) pour que l'index reste cohérent avec les étapes
     * réellement affichées dans le stepper.
     *
     * @param  array<int, CropOrderStatus>|null  $happyPath
     */
    public function furthestHappyPathIndex(?array $happyPath = null): ?int
    {
        $happyValues = array_map(fn (CropOrderStatus $s) => $s->value, $happyPath ?? CropOrderStatus::happyPath());

        $index = array_search($this->status->value, $happyValues, true);
        if ($index !== false) {
            return $index;
        }

        $previous = $this->activities()->latest()->first()?->properties['old']['status'] ?? null;
        $index = $previous ? array_search($previous, $happyValues, true) : false;

        return $index === false ? null : $index;
    }
}

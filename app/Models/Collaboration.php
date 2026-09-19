<?php

namespace App\Models;

use App\Enums\CollaborationDeliveryStatus;
use App\Enums\CollaborationPaymentStatus;
use App\Enums\CollaborationStatus;
use App\Events\CollaborationDeliveryStepMarked;
use App\Events\CollaborationPaymentDeclared;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Agrège l'accord (Accord/Paiement/Livraison, §18) une fois une ConnectionRequest
 * confirmée. `CollaborationPayment`/`CollaborationDelivery` sont des entités enfants
 * pilotées exclusivement à travers ce modèle (pas d'API propre sur les enfants) : c'est
 * lui qui décide des garde-fous et fait avancer `status` en conséquence.
 *
 * Mêmes conventions que ConnectionRequest (Phase 5) : chaque transition a un prédicat
 * `canXxxBy()` réutilisé par CollaborationPolicy et par la méthode elle-même, chaque
 * transition est idempotente (renvoie false plutôt que lever une exception), aucun
 * contournement admin sur les actions réservées aux parties (cf. AppServiceProvider —
 * seul `markDisputed()` reste un pouvoir admin).
 */
class Collaboration extends Model
{
    use LogsActivity;

    protected $fillable = [
        'connection_request_id', 'producer_profile_id', 'buyer_profile_id',
        'agreed_product', 'agreed_quantity', 'agreed_unit', 'agreed_price_total',
        'terms_note', 'status', 'started_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'agreed_quantity' => 'decimal:2',
            'agreed_price_total' => 'integer',
            'status' => CollaborationStatus::class,
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('collaboration');
    }

    /* ----------------------------------------------------------------
     |  Relations
     |---------------------------------------------------------------- */

    public function connectionRequest(): BelongsTo
    {
        return $this->belongsTo(ConnectionRequest::class);
    }

    public function producerProfile(): BelongsTo
    {
        return $this->belongsTo(ProducerProfile::class);
    }

    public function buyerProfile(): BelongsTo
    {
        return $this->belongsTo(BuyerProfile::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CollaborationPayment::class);
    }

    public function delivery(): HasOne
    {
        return $this->hasOne(CollaborationDelivery::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
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

    /** L'autre partie que `$actor` — pour notifier celui qui n'a pas agi (Phase 9). */
    public function otherParty(User $actor): User
    {
        return $this->isProducer($actor) ? $this->buyerProfile->user : $this->producerProfile->user;
    }

    /* ----------------------------------------------------------------
     |  Prédicats de garde
     |---------------------------------------------------------------- */

    public function canDeclarePaymentBy(User $user): bool
    {
        return $this->status === CollaborationStatus::EnCours && $this->isBuyer($user);
    }

    /** Même garde pour confirmer ou contester : c'est la réponse du producteur à une même déclaration. */
    public function canRespondToPaymentBy(User $user): bool
    {
        return $this->status === CollaborationStatus::PaiementDeclare && $this->isProducer($user);
    }

    public function canMarkDeliveryStepBy(User $user, CollaborationDeliveryStatus $to): bool
    {
        if (! in_array($this->status, [CollaborationStatus::PaiementConfirme, CollaborationStatus::LivraisonEnCours], true)) {
            return false;
        }

        $current = $this->delivery?->status ?? CollaborationDeliveryStatus::Prevue;

        return match (true) {
            $to === CollaborationDeliveryStatus::EnCours && $current === CollaborationDeliveryStatus::Prevue => $this->isProducer($user),
            $to === CollaborationDeliveryStatus::Effectuee && $current === CollaborationDeliveryStatus::EnCours => $this->isProducer($user),
            $to === CollaborationDeliveryStatus::Receptionnee && $current === CollaborationDeliveryStatus::Effectuee => $this->isBuyer($user),
            default => false,
        };
    }

    public function canBeCancelledBy(User $user): bool
    {
        return $this->status === CollaborationStatus::EnCours && $this->isParty($user);
    }

    public function canBeDisputedBy(User $user): bool
    {
        return $user->isAdmin()
            && ! in_array($this->status, [CollaborationStatus::Terminee, CollaborationStatus::Annulee, CollaborationStatus::Litige], true);
    }

    /** Résoudre un litige = pouvoir admin symétrique à markDisputed() (Phase 10, §37). */
    public function canResolveDisputeBy(User $user): bool
    {
        return $user->isAdmin() && $this->status === CollaborationStatus::Litige;
    }

    /** Une seule fois par (collaboration, auteur) — cf. contrainte unique en base. */
    public function canBeReviewedBy(User $user): bool
    {
        return $this->status === CollaborationStatus::Terminee
            && $this->isParty($user)
            && ! $this->reviews()->where('rater_id', $user->id)->exists();
    }

    /* ----------------------------------------------------------------
     |  Transitions
     |---------------------------------------------------------------- */

    public function declarePayment(User $buyer, int $amountDeclared, string $method, ?string $note = null): bool
    {
        if (! $this->canDeclarePaymentBy($buyer)) {
            return false;
        }

        DB::transaction(function () use ($buyer, $amountDeclared, $method, $note) {
            $this->payments()->create([
                'declared_by' => $buyer->id,
                'amount_declared' => $amountDeclared,
                'method' => $method,
                'note' => $note,
                'declared_at' => now(),
                'status' => CollaborationPaymentStatus::Declare,
            ]);
            $this->update(['status' => CollaborationStatus::PaiementDeclare]);
        });

        event(new CollaborationPaymentDeclared($this));

        return true;
    }

    public function confirmPayment(User $producer): bool
    {
        if (! $this->canRespondToPaymentBy($producer)) {
            return false;
        }

        DB::transaction(function () use ($producer) {
            $this->payments()->latest()->first()?->update([
                'status' => CollaborationPaymentStatus::Confirme,
                'confirmed_by' => $producer->id,
                'confirmed_at' => now(),
            ]);
            $this->update(['status' => CollaborationStatus::PaiementConfirme]);
        });

        return true;
    }

    /**
     * Le producteur n'a pas reçu ce qui a été déclaré : on ne bascule PAS en litige
     * (réservé à l'admin) — juste retour à `en_cours` pour que l'acheteur redéclare.
     */
    public function contestPayment(User $producer, string $reason): bool
    {
        if (! $this->canRespondToPaymentBy($producer)) {
            return false;
        }

        DB::transaction(function () use ($producer, $reason) {
            $this->payments()->latest()->first()?->update([
                'status' => CollaborationPaymentStatus::Conteste,
                'confirmed_by' => $producer->id,
                'confirmed_at' => now(),
                'note' => $reason,
            ]);
            $this->update(['status' => CollaborationStatus::EnCours]);
        });

        return true;
    }

    /**
     * Réceptionnée fait directement passer à `terminee` (rien d'autre ne distingue
     * `livraison_confirmee` de `terminee` dans cette V1 — voir CollaborationStatus).
     */
    public function markDeliveryStep(User $actor, CollaborationDeliveryStatus $to): bool
    {
        if (! $this->canMarkDeliveryStepBy($actor, $to)) {
            return false;
        }

        DB::transaction(function () use ($actor, $to) {
            $this->delivery?->update(['status' => $to, 'updated_by' => $actor->id]);

            $newStatus = match ($to) {
                CollaborationDeliveryStatus::EnCours, CollaborationDeliveryStatus::Effectuee => CollaborationStatus::LivraisonEnCours,
                CollaborationDeliveryStatus::Receptionnee => CollaborationStatus::Terminee,
                default => $this->status,
            };

            $this->update([
                'status' => $newStatus,
                'completed_at' => $to === CollaborationDeliveryStatus::Receptionnee ? now() : $this->completed_at,
            ]);
        });

        event(new CollaborationDeliveryStepMarked($this, $actor));

        return true;
    }

    public function cancel(User $actor): bool
    {
        if (! $this->canBeCancelledBy($actor)) {
            return false;
        }

        $this->update(['status' => CollaborationStatus::Annulee]);

        return true;
    }

    /** Motif journalisé via l'activity log (pas de colonne dédiée — cf. migration). */
    public function markDisputed(User $admin, string $reason): bool
    {
        if (! $this->canBeDisputedBy($admin)) {
            return false;
        }

        $this->update(['status' => CollaborationStatus::Litige]);

        activity('collaboration')
            ->performedOn($this)
            ->causedBy($admin)
            ->withProperties(['reason' => $reason])
            ->log('Litige signalé : '.$reason);

        return true;
    }

    /**
     * Un litige n'est pas une impasse : l'admin le résout en remettant la collaboration
     * `en_cours` (les parties reprennent la main — jamais directement `terminee`, ce
     * serait décider à leur place qu'elles se sont entendues). Motif journalisé de la
     * même façon que markDisputed() (pas de colonne dédiée).
     */
    public function resolveDispute(User $admin, string $resolution): bool
    {
        if (! $this->canResolveDisputeBy($admin)) {
            return false;
        }

        $this->update(['status' => CollaborationStatus::EnCours]);

        activity('collaboration')
            ->performedOn($this)
            ->causedBy($admin)
            ->withProperties(['resolution' => $resolution])
            ->log('Litige résolu : '.$resolution);

        return true;
    }

    /**
     * Le prix négocié final (`agreed_price_total`) n'est renseigné par aucun écran de
     * cette V1 — la négociation reste dans la messagerie (Phase 6) — donc toujours `null`
     * en pratique. Pour les statistiques (§37, « volume ESTIMÉ »), on retombe sur le prix
     * indicatif de l'offre/besoin d'origine : c'est précisément pourquoi ce chiffre est un
     * estimé, jamais un montant réellement réconcilié.
     */
    public function estimatedValue(): int
    {
        if ($this->agreed_price_total !== null) {
            return $this->agreed_price_total;
        }

        $cr = $this->connectionRequest;

        return $cr?->cropOffer?->price_indicative ?? $cr?->buyerNeed?->budget_indicative ?? 0;
    }
}

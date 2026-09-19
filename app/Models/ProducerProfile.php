<?php

namespace App\Models;

use App\Enums\ActivityType;
use App\Enums\ReviewDirection;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ProducerProfile extends Model
{
    use LogsActivity;

    // verified_at / verified_by volontairement absents : jamais renseignables par
    // l'utilisateur, réservés à un forceFill admin (vérification producteur, Phase 8 —
    // voir verify()/rejectVerification()).
    protected $fillable = [
        'user_id', 'business_name', 'bio', 'zone', 'activity_type',
        'capacity_note', 'years_active', 'logo_path',
    ];

    protected function casts(): array
    {
        return [
            'activity_type' => ActivityType::class,
            'years_active' => 'integer',
            'verified_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['verified_at', 'verified_by'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('producer_profile');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function cropOffers(): HasMany
    {
        return $this->hasMany(CropOffer::class);
    }

    public function connectionRequests(): HasMany
    {
        return $this->hasMany(ConnectionRequest::class);
    }

    public function cropOrders(): HasMany
    {
        return $this->hasMany(CropOrder::class);
    }

    /**
     * `Review.ratee_id` référence `users.id` — pas de FK directe vers producer_profiles,
     * d'où ces clés personnalisées (`user_id` local sert de clé de jointure). Exclut les
     * avis masqués par un admin (Phase 12, §25) — filtré ICI plutôt que dans chaque appelant
     * pour que `averageRating()`/`reviewsCount()` ET les `withAvg`/`withCount` publics
     * (Public\Producers) en bénéficient automatiquement, sans jamais y toucher.
     */
    public function receivedReviews(): HasMany
    {
        return $this->hasMany(Review::class, 'ratee_id', 'user_id')
            ->where('direction', ReviewDirection::AcheteurVersProducteur)
            ->whereNull('hidden_at');
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    /** Compte propriétaire actif (pas suspendu) — condition de visibilité publique. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereHas('user', fn (Builder $q) => $q->where('status', UserStatus::Actif));
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->whereNotNull('verified_at');
    }

    /**
     * Moyenne simple, aucune pondération/anti-manipulation avancée (volontairement
     * basique en V1, cf. cahier des charges). Préférer les colonnes `avg_rating`/
     * `reviews_count` calculées par `withAvg`/`withCount` dans une liste (évite le N+1) —
     * ces deux méthodes ne sont utiles que ponctuellement (fiche d'un seul producteur).
     */
    public function averageRating(): ?float
    {
        return $this->receivedReviews()->avg('rating');
    }

    public function reviewsCount(): int
    {
        return $this->receivedReviews()->count();
    }

    /** Idempotent : renvoie false si déjà vérifié (même esprit que Payment::confirm()). */
    public function verify(User $admin): bool
    {
        if ($this->isVerified()) {
            return false;
        }

        $this->forceFill(['verified_at' => now(), 'verified_by' => $admin->id])->save();

        return true;
    }

    /** Révoque une vérification existante (ex. producteur signalé après coup) — idempotent. */
    public function rejectVerification(): bool
    {
        if (! $this->isVerified()) {
            return false;
        }

        $this->forceFill(['verified_at' => null, 'verified_by' => null])->save();

        return true;
    }
}

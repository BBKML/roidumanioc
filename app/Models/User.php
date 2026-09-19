<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Support\PhoneNumber;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

// Vérification e-mail non requise en v1 (cf. maquette). Pour l'activer :
// implements \Illuminate\Contracts\Auth\MustVerifyEmail + remettre 'verified' sur les routes.
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, LogsActivity, Notifiable;

    /**
     * `role`/`status` sont volontairement ABSENTS de cette liste (contrairement à un
     * premier état de ce fichier) : ce sont les deux champs les plus sensibles du modèle
     * (élévation de privilège / réactivation d'un compte suspendu) et ne doivent jamais
     * pouvoir être définis par un simple `create()`/`fill()` alimenté par une requête,
     * même par erreur dans un futur refactor. Les deux seuls sites légitimes qui les
     * écrivent (`RegisteredUserController` ne les écrit jamais — défauts BDD ; création
     * manuelle par un admin dans `Admin\Members::createMember` ; création de compte Google
     * dans `Auth\GoogleController::resolveUser`) passent explicitement par `forceFill()`.
     */
    protected $fillable = [
        'name', 'email', 'password', 'password_changed_at',
        'phone', 'city',
        'provider', 'provider_id', 'joined_at',
    ];

    protected $hidden = [
        'password', 'remember_token', 'provider_id',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'joined_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'delivery_strikes' => 'integer',
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
        ];
    }

    /**
     * Numéro toujours stocké normalisé (chiffres uniquement) : sert d'identifiant
     * de connexion, doit donc être comparable indépendamment du formatage saisi.
     */
    protected function phone(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => PhoneNumber::normalize($value),
        );
    }

    /* ----------------------------------------------------------------
     |  Rôles
     |---------------------------------------------------------------- */

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isApprenant(): bool
    {
        return $this->role === UserRole::Apprenant;
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Actif;
    }

    /* ----------------------------------------------------------------
     |  Relations
     |---------------------------------------------------------------- */

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function validEnrollments(): HasMany
    {
        return $this->enrollments()->where('status', EnrollmentStatus::Validee);
    }

    public function lessonProgress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function marketplaceListings(): HasMany
    {
        return $this->hasMany(MarketplaceListing::class);
    }

    public function communityPosts(): HasMany
    {
        return $this->hasMany(CommunityPost::class);
    }

    /**
     * Profils optionnels et cumulables, superposés au rôle/statut existants
     * (un compte peut être apprenant + producteur + acheteur en même temps).
     */
    public function producerProfile(): HasOne
    {
        return $this->hasOne(ProducerProfile::class);
    }

    public function buyerProfile(): HasOne
    {
        return $this->hasOne(BuyerProfile::class);
    }

    /**
     * Favoris d'un acheteur sur des producteurs (§11 — tableau de bord acheteur). Ouvert à
     * tout utilisateur connecté (pas seulement un `buyerProfile` déjà créé) — favoriter en
     * parcourant /producteurs peut précéder l'activation de l'espace acheteur, cf.
     * FavoriteController. Table pivot simple (`favorites`) — pas de modèle Eloquent dédié.
     */
    public function favoriteProducers(): BelongsToMany
    {
        return $this->belongsToMany(ProducerProfile::class, 'favorites')->withTimestamps();
    }

    /* ----------------------------------------------------------------
     |  Helpers
     |---------------------------------------------------------------- */

    public function initials(): string
    {
        return collect(explode(' ', $this->name))
            ->filter()
            ->take(2)
            ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))
            ->implode('');
    }

    public function isEnrolledIn(Formation $formation): bool
    {
        return $this->validEnrollments()->where('formation_id', $formation->id)->exists();
    }

    public function isProducer(): bool
    {
        return $this->producerProfile !== null;
    }

    public function isBuyer(): bool
    {
        return $this->buyerProfile !== null;
    }

    /** Compte connecté uniquement via Google (aucun mot de passe choisi par l'utilisateur). */
    public function isOAuthOnly(): bool
    {
        return $this->provider === 'google' && is_null($this->password_changed_at);
    }

    /** Ce client peut-il choisir « paiement à la livraison » ? */
    public function canPayOnDelivery(): bool
    {
        return $this->isActive() && (int) $this->delivery_strikes < 2;
    }

    public function openDeliveryOrdersCount(): int
    {
        return $this->orders()
            ->where('payment_mode', 'on_delivery')
            ->whereIn('status', ['validee', 'expediee'])
            ->count();
    }

    public function scopeActiveAdmins(Builder $query): Builder
    {
        return $query->where('role', UserRole::Admin)->where('status', UserStatus::Actif);
    }

    /**
     * E-mails des admins actifs, en copie de certaines notifications déjà envoyées à une autre
     * adresse (contact, prospects de campagne) — l'admin ne doit rater aucun message même quand
     * il part vers une boîte partagée. `$except` évite un doublon To/Cc sur la même adresse.
     */
    public static function activeAdminEmails(?string $except = null): array
    {
        return static::query()->activeAdmins()
            ->whereNotNull('email')
            ->pluck('email')
            ->reject(fn ($email) => $except && strcasecmp($email, $except) === 0)
            ->values()
            ->all();
    }

    /**
     * Vrai si retirer/suspendre ce compte laisserait le royaume sans administrateur.
     *
     * `$lockForUpdate` : à passer à `true` dans une `DB::transaction()` avant d'écrire —
     * sans verrou, deux suspensions croisées quasi simultanées entre les 2 seuls admins
     * actifs peuvent chacune lire "il en reste un autre" avant que l'autre n'écrive, et
     * aboutir toutes les deux à zéro administrateur actif (race TOCTOU). Le verrou porte
     * volontairement sur TOUS les admins actifs (soi-même inclus), pas seulement les
     * autres : verrouiller uniquement "les autres" ferait porter les deux transactions
     * concurrentes sur des lignes disjointes (l'une verrouille A, l'autre verrouille B) et
     * ne les sérialiserait jamais l'une contre l'autre.
     */
    public function isLastActiveAdmin(bool $lockForUpdate = false): bool
    {
        if (! $this->isAdmin() || ! $this->isActive()) {
            return false;
        }

        $query = static::query()->activeAdmins();

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->pluck('id')->reject(fn ($id) => $id === $this->id)->isEmpty();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'role', 'status', 'provider'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('user');
    }

    public function hasPendingPaymentFor(Formation $formation): bool
    {
        return $this->enrollments()
            ->where('formation_id', $formation->id)
            ->where('status', EnrollmentStatus::Paiement)
            ->exists();
    }
}

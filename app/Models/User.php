<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// Vérification e-mail non requise en v1 (cf. maquette). Pour l'activer :
// implements \Illuminate\Contracts\Auth\MustVerifyEmail + remettre 'verified' sur les routes.
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password',
        'phone', 'city', 'role', 'status',
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
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
        ];
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
        return $this->enrollments()->where('status', \App\Enums\EnrollmentStatus::Validee);
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

    public function hasPendingPaymentFor(Formation $formation): bool
    {
        return $this->enrollments()
            ->where('formation_id', $formation->id)
            ->where('status', \App\Enums\EnrollmentStatus::Paiement)
            ->exists();
    }
}

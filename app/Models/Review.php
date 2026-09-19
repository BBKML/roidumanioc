<?php

namespace App\Models;

use App\Enums\ReviewDirection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Volontairement basique en V1 (pas de pondération, pas d'anti-manipulation avancé) —
 * juste une moyenne simple et un compteur, cf. ProducerProfile::averageRating(). Créée
 * exclusivement via App\Actions\SubmitReview ; le CONTENU (rating/criteria/comment) reste
 * immuable après soumission — aucune méthode d'édition/suppression exposée nulle part dans
 * l'app (cf. Sécurité). `hidden_*` (Phase 12, §25) est la seule exception : un pouvoir de
 * MODÉRATION admin qui masque un avis abusif de l'affichage/des moyennes publiques, sans
 * jamais toucher au contenu déposé — mêmes conventions que ProducerProfile::verify() (idem
 * potent, forceFill uniquement, jamais dans $fillable).
 */
class Review extends Model
{
    use LogsActivity;

    protected $fillable = ['collaboration_id', 'rater_id', 'ratee_id', 'direction', 'rating', 'criteria', 'comment'];

    protected function casts(): array
    {
        return [
            'direction' => ReviewDirection::class,
            'rating' => 'integer',
            'criteria' => 'array',
            'hidden_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['hidden_at', 'hidden_by', 'hidden_reason'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('review');
    }

    public function collaboration(): BelongsTo
    {
        return $this->belongsTo(Collaboration::class);
    }

    public function rater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rater_id');
    }

    public function ratee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ratee_id');
    }

    public function hiddenBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hidden_by');
    }

    public function isHidden(): bool
    {
        return $this->hidden_at !== null;
    }

    /** Idempotent, comme ProducerProfile::verify() — renvoie false si déjà masqué. */
    public function hide(User $admin, string $reason): bool
    {
        if ($this->isHidden()) {
            return false;
        }

        $this->forceFill([
            'hidden_at' => now(),
            'hidden_by' => $admin->id,
            'hidden_reason' => $reason,
        ])->save();

        return true;
    }

    /** Réaffiche un avis masqué à tort — idempotent. */
    public function unhide(): bool
    {
        if (! $this->isHidden()) {
            return false;
        }

        $this->forceFill(['hidden_at' => null, 'hidden_by' => null, 'hidden_reason' => null])->save();

        return true;
    }
}

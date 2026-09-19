<?php

namespace App\Models;

use App\Enums\EnrollmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Payment extends Model
{
    use LogsActivity;

    protected $fillable = [
        'reference', 'user_id', 'payable_type', 'payable_id',
        'enrollment_id', 'order_id', 'label', 'amount', 'quantity', 'method', 'status',
        'declared_amount', 'transaction_id', 'proof_path', 'proof_hash', 'proof_mime',
        'check_result', 'rejection_reason', 'confirmed_by', 'confirmed_at', 'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'quantity' => 'integer',
            'declared_amount' => 'integer',
            'method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'check_result' => 'array',
            'confirmed_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Payment $payment) {
            $payment->reference ??= static::generateReference();
            $payment->submitted_at ??= now();
            $payment->status ??= PaymentStatus::AVerifier;
            $payment->quantity ??= 1;
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'confirmed_by', 'rejection_reason'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('payment');
    }

    public static function generateReference(): string
    {
        do {
            $ref = 'RDM-'.random_int(1000, 9999);
        } while (static::where('reference', $ref)->exists());

        return $ref;
    }

    /* ---------------- Relations ---------------- */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    /* ---------------- Scopes ---------------- */

    public function scopeToVerify(Builder $query): Builder
    {
        return $query->where('status', PaymentStatus::AVerifier);
    }

    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('status', PaymentStatus::Confirme);
    }

    public function scopeRefused(Builder $query): Builder
    {
        return $query->where('status', PaymentStatus::Refuse);
    }

    /* ---------------- Contrôle automatique ---------------- */

    /**
     * Compare les informations SAISIES par le client au prix attendu et cherche
     * des signaux de fraude. Ne prouve JAMAIS que l'argent est arrivé : l'admin
     * doit vérifier la réception réelle sur le compte marchand avant de confirmer.
     */
    public function runAutoCheck(): array
    {
        $flags = [];

        // 1) Capture présente ?
        $proof = filled($this->proof_path) ? 'ok' : 'missing';
        if ($proof === 'missing') {
            $flags[] = 'proof_missing';
        }

        // 2) Montant déclaré vs attendu
        $declared = (int) $this->declared_amount;
        $gap = $declared - (int) $this->amount;
        $amount = $gap === 0 ? 'ok' : ($gap < 0 ? 'insufficient' : 'excess');
        if ($amount === 'insufficient') {
            $flags[] = 'amount_insufficient';
        }
        if ($amount === 'excess') {
            $flags[] = 'amount_excess';
        }

        // 3) Numéro de transaction déjà utilisé sur un autre paiement ?
        if (filled($this->transaction_id) && static::query()
            ->where('id', '!=', $this->id)
            ->where('transaction_id', $this->transaction_id)
            ->exists()) {
            $flags[] = 'duplicate_transaction';
        }

        // 4) Même capture (empreinte) déjà téléversée ailleurs ?
        if (filled($this->proof_hash) && static::query()
            ->where('id', '!=', $this->id)
            ->where('proof_hash', $this->proof_hash)
            ->exists()) {
            $flags[] = 'duplicate_proof';
        }

        // 5) Plusieurs paiements en attente pour le même client ?
        if (static::query()
            ->where('id', '!=', $this->id)
            ->where('user_id', $this->user_id)
            ->where('status', PaymentStatus::AVerifier)
            ->exists()) {
            $flags[] = 'multiple_pending';
        }

        $risk = match (true) {
            (bool) array_intersect($flags, ['duplicate_transaction', 'duplicate_proof']) => 'high',
            (bool) array_intersect($flags, ['proof_missing', 'amount_insufficient', 'multiple_pending']) => 'medium',
            default => 'low',
        };

        return $this->check_result = compact('proof', 'amount', 'gap', 'declared', 'flags', 'risk');
    }

    public function isAmountConform(): bool
    {
        return ($this->check_result['amount'] ?? null) === 'ok';
    }

    public function risk(): string
    {
        return $this->check_result['risk'] ?? 'medium';
    }

    /** @return array<int,string> */
    public function flags(): array
    {
        return $this->check_result['flags'] ?? [];
    }

    public function hasFlag(string $flag): bool
    {
        return in_array($flag, $this->flags(), true);
    }

    public const FLAG_LABELS = [
        'proof_missing' => 'Aucune capture de reçu',
        'amount_insufficient' => 'Montant déclaré inférieur au prix',
        'amount_excess' => 'Montant déclaré supérieur au prix',
        'duplicate_transaction' => 'N° de transaction déjà utilisé sur un autre paiement',
        'duplicate_proof' => 'Capture identique à une autre déjà envoyée',
        'multiple_pending' => 'Ce client a plusieurs paiements en attente',
    ];

    public static function flagLabel(string $flag): string
    {
        return self::FLAG_LABELS[$flag] ?? $flag;
    }

    /* ---------------- Transitions (admin only) ---------------- */

    /**
     * Confirme le paiement et débloque l'inscription ou la commande liée.
     * Idempotent : ne fait rien si le paiement n'est plus « à vérifier ».
     */
    /**
     * Un admin ne peut jamais confirmer/rejeter SON PROPRE paiement (self-dealing) — même
     * garde-fou de principe que les exclusions `Gate::before` sur Collaboration/
     * ConnectionRequest/Conversation/Review (« jamais confirmer à la place d'une partie »,
     * ici « jamais juge et partie »). Rien n'empêche structurellement un compte admin
     * d'être aussi client de la plateforme (formation/boutique) ; sans ce garde-fou, il
     * pourrait déclarer un paiement puis se l'auto-confirmer via son propre accès admin.
     */
    public function canBeDecidedBy(User $admin): bool
    {
        return $this->status === PaymentStatus::AVerifier && $this->user_id !== $admin->id;
    }

    public function confirm(User $admin): bool
    {
        if (! $this->canBeDecidedBy($admin)) {
            return false;
        }

        DB::transaction(function () use ($admin) {
            $this->update([
                'status' => PaymentStatus::Confirme,
                'confirmed_by' => $admin->id,
                'confirmed_at' => now(),
                'rejection_reason' => null,
            ]);

            $this->enrollment?->markValidated();
            $this->order?->markValidated();
        });

        return true;
    }

    public function reject(User $admin, ?string $reason = null): bool
    {
        if (! $this->canBeDecidedBy($admin)) {
            return false;
        }

        DB::transaction(function () use ($admin, $reason) {
            $this->update([
                'status' => PaymentStatus::Refuse,
                'confirmed_by' => $admin->id,
                'confirmed_at' => now(),
                'rejection_reason' => $reason,
            ]);

            $this->enrollment?->update(['status' => EnrollmentStatus::Refuse]);
            $this->order?->update(['status' => OrderStatus::Refuse]);
        });

        return true;
    }

    public function canBeViewedBy(User $user): bool
    {
        return $user->isAdmin() || $this->user_id === $user->id;
    }

    /**
     * Lien WhatsApp pré-rempli pour l'envoi de la preuve.
     */
    public function whatsappLink(): string
    {
        $number = Str::of(PaymentSetting::current()->whatsapp ?? '')->replaceMatches('/\D+/', '');

        $text = "Bonjour, je viens d'effectuer le paiement de ma commande *{$this->reference}*.\n"
            ."Objet : {$this->label}\n"
            .'Montant : '.number_format($this->amount, 0, ',', ' ')." FCFA\n"
            .'Moyen : '.$this->method->label()."\n\n"
            .'Voici la capture du reçu :';

        return "https://wa.me/{$number}?text=".rawurlencode($text);
    }
}

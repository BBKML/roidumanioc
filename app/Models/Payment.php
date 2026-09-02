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
use Illuminate\Support\Str;

class Payment extends Model
{
    protected $fillable = [
        'reference', 'user_id', 'payable_type', 'payable_id',
        'enrollment_id', 'order_id', 'label', 'amount', 'method', 'status',
        'declared_amount', 'transaction_id', 'proof_path', 'check_result',
        'confirmed_by', 'confirmed_at', 'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
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
        });
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

    /* ---------------- Contrôle automatique ---------------- */

    /**
     * Compare le montant déclaré par le client au prix attendu.
     * Ne prouve PAS que l'argent est arrivé — aide seulement au tri.
     */
    public function runAutoCheck(): array
    {
        if (blank($this->proof_path)) {
            return $this->check_result = ['proof' => 'missing'];
        }

        $declared = (int) $this->declared_amount;
        $gap = $declared - (int) $this->amount;

        return $this->check_result = [
            'proof' => 'ok',
            'amount' => $gap === 0 ? 'ok' : ($gap < 0 ? 'insufficient' : 'excess'),
            'gap' => $gap,
            'declared' => $declared,
        ];
    }

    public function isAmountConform(): bool
    {
        return ($this->check_result['amount'] ?? null) === 'ok';
    }

    /**
     * Confirme le paiement et débloque la formation ou la commande liée.
     */
    public function confirm(User $admin): void
    {
        $this->update([
            'status' => PaymentStatus::Confirme,
            'confirmed_by' => $admin->id,
            'confirmed_at' => now(),
        ]);

        $this->enrollment?->markValidated();
        $this->order?->markValidated();
    }

    public function reject(User $admin): void
    {
        $this->update([
            'status' => PaymentStatus::Refuse,
            'confirmed_by' => $admin->id,
            'confirmed_at' => now(),
        ]);

        $this->enrollment?->update(['status' => EnrollmentStatus::Refuse]);
        $this->order?->update(['status' => OrderStatus::Refuse]);
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

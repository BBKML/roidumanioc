<?php

namespace App\Models;

use App\Enums\OrderPaymentMode;
use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Order extends Model
{
    use LogsActivity;

    protected $fillable = [
        'reference', 'user_id', 'customer_name', 'contact_phone',
        'delivery_address', 'delivery_city',
        'orderable_type', 'orderable_id', 'item_label', 'quantity',
        'amount', 'delivery_fee', 'status', 'payment_mode',
        'customer_note', 'handled_by', 'ordered_at', 'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'amount' => 'integer',
            'delivery_fee' => 'integer',
            'status' => OrderStatus::class,
            'payment_mode' => OrderPaymentMode::class,
            'ordered_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            $order->reference ??= static::generateReference();
            $order->ordered_at ??= now();
        });

        // Réservation / restitution du stock produit.
        static::created(function (Order $order) {
            $order->adjustStock(-$order->quantity);
        });
        static::updated(function (Order $order) {
            if ($order->wasChanged('status') && $order->status === OrderStatus::Refuse) {
                $order->adjustStock($order->quantity);
            }
        });
    }

    public static function generateReference(): string
    {
        do {
            $ref = 'CMD-'.random_int(1000, 9999);
        } while (static::where('reference', $ref)->exists());

        return $ref;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'handled_by'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('order');
    }

    /* ---------------- Relations ---------------- */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orderable(): MorphTo
    {
        return $this->morphTo();
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function handledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    /* ---------------- Helpers ---------------- */

    public function total(): int
    {
        return (int) $this->amount + (int) $this->delivery_fee;
    }

    public function isPayOnDelivery(): bool
    {
        return $this->payment_mode === OrderPaymentMode::OnDelivery;
    }

    /** Lien wa.me pré-rempli pour prévenir le client (gratuit, sans API). */
    public function whatsappLink(): ?string
    {
        $number = preg_replace('/\D+/', '', (string) $this->contact_phone);
        if ($number === '') {
            return null;
        }
        // Numéro local ivoirien -> préfixe 225.
        if (strlen($number) <= 10) {
            $number = '225'.ltrim($number, '0');
        }

        $status = $this->status->label();
        $text = "Bonjour, votre commande *{$this->reference}* ({$this->item_label}) : {$status}."
            .($this->isPayOnDelivery() ? ' Montant à préparer : '.number_format($this->total(), 0, ',', ' ').' FCFA.' : '');

        return 'https://wa.me/'.$number.'?text='.rawurlencode($text);
    }

    private function adjustStock(int $delta): void
    {
        if ($this->orderable instanceof ShopProduct) {
            $this->orderable->decrement('stock', -$delta);
        }
    }

    /* ---------------- Transitions ---------------- */

    public function markValidated(): void
    {
        if ($this->status === OrderStatus::Paiement) {
            $this->update(['status' => OrderStatus::Validee]);
        }
    }

    public function markShipped(User $admin): void
    {
        $this->update(['status' => OrderStatus::Expediee, 'handled_by' => $admin->id]);
    }

    public function markDelivered(User $admin): void
    {
        $this->update([
            'status' => OrderStatus::Livree,
            'handled_by' => $admin->id,
            'delivered_at' => now(),
        ]);
    }

    public function cancel(?User $admin = null): void
    {
        if ($this->status->isOpen()) {
            $this->update([
                'status' => OrderStatus::Refuse,
                'handled_by' => $admin?->id,
            ]);
        }
    }
}

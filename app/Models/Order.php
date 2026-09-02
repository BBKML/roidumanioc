<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Order extends Model
{
    protected $fillable = [
        'user_id', 'customer_name', 'orderable_type', 'orderable_id',
        'item_label', 'quantity', 'amount', 'status', 'ordered_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'amount' => 'integer',
            'status' => OrderStatus::class,
            'ordered_at' => 'datetime',
        ];
    }

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

    public function markValidated(): void
    {
        $this->update(['status' => OrderStatus::Validee]);
    }
}

<?php

namespace App\Models;

use App\Enums\DeliveryAssistStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Sous-objet 1-ligne-par-commande (§10) — sans logique propre, pilotée exclusivement via
 * App\Models\CropOrder::markDeliveryAssistStep() (même doctrine que CollaborationDelivery).
 */
class DeliveryAssist extends Model
{
    protected $fillable = [
        'crop_order_id', 'requested_by', 'status', 'courier_name', 'courier_contact',
        'admin_note', 'updated_by', 'requested_at', 'delivered_at', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => DeliveryAssistStatus::class,
            'requested_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function cropOrder(): BelongsTo
    {
        return $this->belongsTo(CropOrder::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

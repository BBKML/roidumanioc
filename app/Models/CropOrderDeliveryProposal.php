<?php

namespace App\Models;

use App\Enums\DeliveryProposalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Une ligne de l'historique de négociation des frais de livraison — sans logique propre,
 * pilotée exclusivement via App\Models\CropOrder (même doctrine que CollaborationPayment).
 */
class CropOrderDeliveryProposal extends Model
{
    protected $fillable = ['crop_order_id', 'proposed_by', 'amount', 'note', 'status'];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'status' => DeliveryProposalStatus::class,
        ];
    }

    public function cropOrder(): BelongsTo
    {
        return $this->belongsTo(CropOrder::class);
    }

    public function proposedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_by');
    }
}

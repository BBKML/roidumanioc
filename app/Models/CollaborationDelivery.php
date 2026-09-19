<?php

namespace App\Models;

use App\Enums\CollaborationDeliveryStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollaborationDelivery extends Model
{
    protected $fillable = ['collaboration_id', 'status', 'updated_by'];

    protected function casts(): array
    {
        return [
            'status' => CollaborationDeliveryStatus::class,
        ];
    }

    public function collaboration(): BelongsTo
    {
        return $this->belongsTo(Collaboration::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

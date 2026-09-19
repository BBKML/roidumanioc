<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CropOfferPhoto extends Model
{
    protected $fillable = ['crop_offer_id', 'path', 'position'];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    public function cropOffer(): BelongsTo
    {
        return $this->belongsTo(CropOffer::class);
    }
}

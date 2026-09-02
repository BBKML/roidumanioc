<?php

namespace App\Models;

use App\Enums\ListingStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MarketplaceListing extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'type', 'title', 'location', 'price_label',
        'seller_name', 'is_official', 'image_path', 'status',
    ];

    protected function casts(): array
    {
        return [
            'is_official' => 'boolean',
            'status' => ListingStatus::class,
        ];
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ListingStatus::Validee);
    }
}

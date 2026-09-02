<?php

namespace App\Models;

use App\Enums\PostStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommunityPost extends Model
{
    protected $fillable = ['user_id', 'author_name', 'body', 'status', 'replies_count'];

    protected function casts(): array
    {
        return [
            'status' => PostStatus::class,
            'replies_count' => 'integer',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(CommunityReply::class);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('status', PostStatus::Visible);
    }
}

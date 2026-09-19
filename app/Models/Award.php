<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedFallback;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Award extends Model
{
    use HasLocalizedFallback;

    protected $fillable = ['year', 'title', 'title_en', 'description', 'description_en', 'is_published', 'position'];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->orderBy('position');
    }
}

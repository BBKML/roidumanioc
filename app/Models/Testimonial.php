<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedFallback;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    use HasLocalizedFallback;

    protected $fillable = ['quote', 'quote_en', 'author_name', 'author_role', 'author_role_en', 'is_published', 'position'];

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

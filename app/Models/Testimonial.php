<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
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

    /** Valeur du champ $field dans la langue courante, repli sur le français si vide. */
    public function localized(string $field): ?string
    {
        if (app()->getLocale() !== config('locales.default')) {
            $translated = $this->{"{$field}_en"} ?? null;
            if (filled($translated)) {
                return $translated;
            }
        }

        return $this->{$field};
    }
}

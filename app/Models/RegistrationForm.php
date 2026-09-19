<?php

namespace App\Models;

use App\Enums\RegistrationFormStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RegistrationForm extends Model
{
    protected $fillable = [
        'title', 'slug', 'status', 'subtitle', 'cover_image_path', 'whatsapp_number',
        'intro', 'objectives', 'schedule_info', 'program', 'certifications',
        'price_amount', 'price_note', 'payment_methods',
        'early_bird_deadline', 'deposit_amount', 'deposit_note',
    ];

    protected function casts(): array
    {
        return [
            'status' => RegistrationFormStatus::class,
            'early_bird_deadline' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected static function booted(): void
    {
        static::creating(function (RegistrationForm $form) {
            $form->status ??= RegistrationFormStatus::Brouillon;
            if (blank($form->slug)) {
                $form->slug = $form->uniqueSlug($form->title);
            }
        });

        static::deleting(function (RegistrationForm $form) {
            if ($form->cover_image_path) {
                Storage::disk('public')->delete($form->cover_image_path);
            }
        });
    }

    public function leads(): HasMany
    {
        return $this->hasMany(RegistrationLead::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', RegistrationFormStatus::Publiee);
    }

    public function isPublished(): bool
    {
        return $this->status === RegistrationFormStatus::Publiee;
    }

    public function publicUrl(): string
    {
        return route('inscription.show', $this);
    }

    public function coverImageUrl(): ?string
    {
        return $this->cover_image_path ? route('inscription.image', $this) : null;
    }

    /** L'offre « premiers inscrits » est active tant que l'échéance n'est pas dépassée. */
    public function isEarlyBirdActive(): bool
    {
        return $this->early_bird_deadline !== null && $this->early_bird_deadline->isFuture();
    }

    /**
     * Dimensions réelles de l'affiche — permet de réserver l'espace exact dans la page
     * (attributs width/height) pour que la section suivante ne « saute » pas par-dessus
     * pendant le chargement de l'image.
     */
    public function coverImageDimensions(): ?array
    {
        if (! $this->cover_image_path) {
            return null;
        }

        $disk = Storage::disk('public');
        if (! $disk->exists($this->cover_image_path)) {
            return null;
        }

        $size = @getimagesize($disk->path($this->cover_image_path));

        return $size ? ['width' => $size[0], 'height' => $size[1]] : null;
    }

    /** Explose un champ multi-ligne en liste (une entrée par ligne non vide). */
    public function lines(string $field): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $this->{$field}))
            ->map(fn ($line) => trim($line))
            ->filter(fn ($line) => $line !== '')
            ->values()
            ->all();
    }

    /**
     * Rendu HTML d'un champ « liste » avec mise en forme simple :
     * `**texte**` -> gras, une ligne commençant par `##` -> sous-titre (coupe la liste en deux).
     * Le texte est échappé avant toute mise en forme : aucune injection HTML possible côté admin.
     */
    public function renderLines(string $field): string
    {
        $lines = collect($this->lines($field));

        if ($lines->isEmpty()) {
            return '';
        }

        $html = '';
        $open = false;

        foreach ($lines as $line) {
            if (str_starts_with($line, '##')) {
                if ($open) {
                    $html .= '</ul>';
                    $open = false;
                }
                $html .= '<p class="gform-subheading">'.$this->formatInline(ltrim(substr($line, 2))).'</p>';

                continue;
            }

            if (! $open) {
                $html .= '<ul>';
                $open = true;
            }
            $html .= '<li>'.$this->formatInline($line).'</li>';
        }

        if ($open) {
            $html .= '</ul>';
        }

        return $html;
    }

    /** Rendu HTML d'un champ « paragraphe » (intro, note de tarif…) avec `**gras**`. */
    public function renderText(string $field): string
    {
        $value = (string) $this->{$field};

        return $value !== '' ? $this->formatInline($value) : '';
    }

    private function formatInline(string $text): string
    {
        return preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', e($text));
    }

    public function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'formulaire';
        $slug = $base;
        $i = 2;

        while (static::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}

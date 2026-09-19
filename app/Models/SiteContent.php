<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class SiteContent extends Model
{
    protected $fillable = ['key', 'label', 'data', 'position'];

    /** Types de champ localisables (texte bilingue fr/en) — le champ "image" ne l'est pas. */
    private const LOCALIZABLE_TYPES = ['text', 'textarea', 'html', 'list'];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'position' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::flushCache());
        static::deleted(fn () => static::flushCache());
    }

    private static function flushCache(): void
    {
        foreach (config('locales.supported') as $locale) {
            Cache::forget("site_content.$locale");
        }
    }

    private static function schema(): array
    {
        return require app_path('Livewire/Admin/content-sections.php');
    }

    /**
     * Tout le contenu du site sous forme de tableau [key => data], résolu dans la langue
     * courante (avec repli sur le français si la traduction anglaise est vide), mis en cache
     * séparément par langue.
     */
    public static function payload(): array
    {
        $locale = in_array(app()->getLocale(), config('locales.supported'), true)
            ? app()->getLocale()
            : config('locales.default');

        return Cache::rememberForever("site_content.$locale", function () use ($locale) {
            $schema = static::schema();

            return static::query()
                ->orderBy('position')
                ->get()
                ->mapWithKeys(function (self $s) use ($schema, $locale) {
                    $sectionSchema = $schema[$s->key] ?? null;
                    $data = $sectionSchema ? static::localizeSection($s->data ?? [], $sectionSchema, $locale) : $s->data;

                    return [$s->key => $data];
                })
                ->all();
        });
    }

    public static function section(string $key, mixed $default = null): mixed
    {
        return static::payload()[$key] ?? $default;
    }

    /**
     * Parcourt les champs "localisables" d'une section (texte/textarea/html/liste, y compris
     * dans un éventuel repeater) et leur applique $fn(valeur, type). Les champs "image" et les
     * clés absentes ne sont pas touchés.
     */
    private static function mapLocalizableFields(array $data, array $sectionSchema, callable $fn): array
    {
        foreach ($sectionSchema['fields'] ?? [] as [$name, $type]) {
            if (! in_array($type, self::LOCALIZABLE_TYPES, true) || ! Arr::has($data, $name)) {
                continue;
            }
            Arr::set($data, $name, $fn(Arr::get($data, $name), $type));
        }

        if (isset($sectionSchema['repeater'])) {
            $rep = $sectionSchema['repeater'];
            $items = Arr::get($data, $rep['path'], []);

            foreach ($items as $i => $item) {
                if (! is_array($item)) {
                    continue;
                }
                foreach ($rep['fields'] as [$name, $type]) {
                    if (! in_array($type, self::LOCALIZABLE_TYPES, true) || ! array_key_exists($name, $item)) {
                        continue;
                    }
                    $item[$name] = $fn($item[$name], $type);
                }
                $items[$i] = $item;
            }

            Arr::set($data, $rep['path'], $items);
        }

        return $data;
    }

    /** Donnée brute bilingue (['fr' => …, 'en' => …]) -> valeur pour $locale, repli sur le français. */
    public static function localizeSection(array $data, array $sectionSchema, string $locale): array
    {
        return static::mapLocalizableFields($data, $sectionSchema, function ($value, $type) use ($locale) {
            if (! is_array($value) || (! array_key_exists('fr', $value) && ! array_key_exists('en', $value))) {
                return $value;
            }

            $resolved = $value[$locale] ?? null;

            return filled($resolved) ? $resolved : ($value['fr'] ?? ($type === 'list' ? [] : ''));
        });
    }

    /** Valeur "à plat" (une seule langue, ex. les données de départ du seeder) -> forme bilingue. */
    public static function wrapMonolingual(array $data, string $key): array
    {
        $sectionSchema = static::schema()[$key] ?? null;

        if (! $sectionSchema) {
            return $data;
        }

        return static::mapLocalizableFields($data, $sectionSchema, fn ($value, $type) => [
            'fr' => $value ?? ($type === 'list' ? [] : ''),
            'en' => $type === 'list' ? [] : '',
        ]);
    }
}

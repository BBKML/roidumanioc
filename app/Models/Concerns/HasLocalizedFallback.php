<?php

namespace App\Models\Concerns;

/**
 * Partagé par Testimonial/Award (SiteContent a son propre localizeSection(), qui
 * résout une valeur bilingue brute {"fr":...,"en":...} plutôt qu'une paire de colonnes
 * `$field`/`${field}_en` — deux formes de stockage différentes, donc pas fusionné avec
 * lui malgré la même intention de repli). Extrait ici pour que les deux modèles ne
 * puissent plus diverger silencieusement l'un de l'autre (audit de logique).
 */
trait HasLocalizedFallback
{
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

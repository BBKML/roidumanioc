<?php

/*
 * Langues disponibles sur la vitrine publique (bascule 🇫🇷/🇬🇧 + contenu bilingue du CMS).
 * Source unique de vérité partagée par App\Http\Middleware\SetLocale, la route
 * "locale.switch" et App\Models\SiteContent (localisation du contenu du CMS).
 */
return [
    'supported' => ['fr', 'en'],
    'default' => 'fr',
];

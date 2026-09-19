<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applique la langue d'interface choisie par le visiteur (cookie "locale",
 * posé par la bascule FR/EN de la vitrine publique). Le contenu édité dans
 * le CMS reste en français ; seule l'interface (menus, boutons, pied de
 * page) est concernée — voir lang/{fr,en}/site.php.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->cookie('locale');

        if (in_array($locale, config('locales.supported'), true)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}

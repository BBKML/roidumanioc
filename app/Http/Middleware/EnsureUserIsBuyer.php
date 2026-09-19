<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garde les pages de l'espace acheteur (besoins, etc. — phases suivantes) : redirige
 * vers l'activation (/mon-espace/acheteur) tant qu'aucun BuyerProfile n'existe.
 */
class EnsureUserIsBuyer
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isBuyer()) {
            // Explique la redirection au lieu de la faire en silence (audit UX, Phase 2) —
            // même canal que les autres messages ponctuels de l'espace apprenant
            // (session('flash'), affiché en toast par components.layouts.learner).
            return redirect()->route('learner.buyer')
                ->with('flash', 'Activez d\'abord votre profil acheteur pour accéder à cette page.');
        }

        return $next($request);
    }
}

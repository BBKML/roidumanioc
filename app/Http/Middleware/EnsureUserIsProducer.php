<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Garde les pages de l'espace producteur (offres, etc. — phases suivantes) : redirige
 * vers l'activation (/mon-espace/producteur) tant qu'aucun ProducerProfile n'existe.
 */
class EnsureUserIsProducer
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->isProducer()) {
            // Explique la redirection au lieu de la faire en silence (audit UX, Phase 2) —
            // même canal que les autres messages ponctuels de l'espace apprenant
            // (session('flash'), affiché en toast par components.layouts.learner).
            return redirect()->route('learner.producer')
                ->with('flash', 'Activez d\'abord votre profil producteur pour accéder à cette page.');
        }

        return $next($request);
    }
}

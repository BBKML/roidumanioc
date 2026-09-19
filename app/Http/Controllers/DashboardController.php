<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

/**
 * Aiguillage post-connexion selon le rôle.
 * (Contrôleur invocable plutôt qu'une closure : permet `route:cache` en production.)
 */
class DashboardController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        return redirect()->route(
            auth()->user()->isAdmin() ? 'admin.dashboard' : 'learner.dashboard'
        );
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\ProducerProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Bascule favori/non-favori sur un producteur, depuis le catalogue public /producteurs
 * (§11). Contrôleur invocable classique (pas une action Livewire) précisément pour
 * bénéficier gratuitement du guest→connexion→retour standard de Laravel sur la route
 * `auth` — un visiteur qui clique le cœur est redirigé vers la connexion puis ramené ici
 * (même principe que les routes de mise en relation, cf. CLAUDE.md).
 */
class FavoriteController extends Controller
{
    public function __invoke(ProducerProfile $producerProfile): RedirectResponse
    {
        Auth::user()->favoriteProducers()->toggle($producerProfile->id);

        return back();
    }
}

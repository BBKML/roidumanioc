<?php

namespace App\Http\Controllers;

use App\Enums\FormationStatus;
use App\Models\Award;
use App\Models\Formation;
use App\Models\SiteContent;

class FormationsController extends Controller
{
    public function index()
    {
        $formations = Formation::published()->withCount('lessons')->orderBy('position')->get();

        return view('formations.index', [
            'formations' => $formations,
            'section' => SiteContent::section('formations_section', []),
            // Distinctions réelles (même modèle que /fondateur et l'accueil) affichées en
            // bandeau juste sous le hero — masqué tant qu'aucune n'est publiée.
            'awards' => Award::published()->get(),
        ]);
    }

    public function show(Formation $formation)
    {
        abort_unless($formation->status === FormationStatus::Publiee, 404);

        $formation->loadCount('lessons');

        return view('formations.show', ['formation' => $formation]);
    }
}

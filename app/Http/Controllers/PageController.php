<?php

namespace App\Http\Controllers;

use App\Models\Award;
use App\Models\Event;
use App\Models\Partner;
use App\Models\SiteContent;
use App\Models\Testimonial;

/**
 * Petites pages publiques dédiées, autrefois de simples ancres sur l'accueil — mêmes données
 * (CMS + modèles), mais avec leur propre URL indexable et partageable.
 */
class PageController extends Controller
{
    public function placali()
    {
        return view('pages.placali', [
            'content' => SiteContent::section('placali', []),
        ]);
    }

    public function communaute()
    {
        return view('pages.communaute', [
            'content' => SiteContent::section('communaute', []),
            'testimonials' => Testimonial::published()->get(),
            // Logos partenaires (modèle dédié, comme témoignages/distinctions) défilant
            // en bandeau — masqué tant qu'aucun n'est publié.
            'partners' => Partner::published()->get(),
        ]);
    }

    public function fondateur()
    {
        return view('pages.fondateur', [
            'content' => SiteContent::section('fondateur', []),
            'distinctions' => SiteContent::section('distinctions', []),
            'awards' => Award::published()->get(),
        ]);
    }

    /**
     * Activités à venir (§ demande client) — première vitrine publique du modèle
     * `Event` déjà utilisé par /admin/evenements et le tableau de bord apprenant.
     * `upcoming()` (déjà existant) filtre par statut (planifie/termine), pas par
     * date : c'est l'admin qui bascule un événement passé en « Terminé ».
     */
    public function evenements()
    {
        $events = Event::upcoming()->get();

        // Vedette du hero : l'événement daté le plus proche (compte à rebours pertinent),
        // sinon simplement le premier de la liste (affiché sans compte à rebours) — même
        // logique « vraie donnée en vedette » que la carte flottante de /marketplace,
        // et comme elle, pas retiré de la liste ci-dessous (juste mis en avant en plus).
        $spotlight = $events->filter(fn (Event $e) => $e->starts_at?->isFuture())->sortBy('starts_at')->first()
            ?? $events->first();

        return view('pages.evenements', [
            'events' => $events,
            'spotlight' => $spotlight,
        ]);
    }

    /**
     * Mentions légales / CGU + politique de confidentialité (§44) — texte fourni par
     * Le Roi du Manioc, saisi dans le CMS (section « legal »), jamais rédigé côté code.
     * Tant que le champ correspondant est vide, la page affiche un message d'attente
     * plutôt qu'un texte juridique inventé.
     */
    public function legalNotice()
    {
        return $this->legalPage('mentions_legales', 'Mentions légales');
    }

    public function privacyPolicy()
    {
        return $this->legalPage('politique_confidentialite', 'Politique de confidentialité');
    }

    /**
     * Charte d'utilisation (§44 — audit V1) : règles de publication, règles de comportement,
     * politique de gestion des litiges et politique concernant les paiements, regroupées en
     * un seul document plutôt que 4 pages distinctes (cf. routes/web.php).
     */
    public function usageCharter()
    {
        return $this->legalPage('charte_utilisation', "Charte d'utilisation");
    }

    private function legalPage(string $field, string $title)
    {
        return view('pages.legal', [
            'title' => $title,
            'html' => SiteContent::section('legal', [])[$field] ?? null,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\CollaborationStatus;
use App\Models\Award;
use App\Models\Collaboration;
use App\Models\Formation;
use App\Models\MarketplaceListing;
use App\Models\ProducerProfile;
use App\Models\ShopProduct;
use App\Models\SiteContent;
use App\Models\Testimonial;

/**
 * Page d'accueil du site vitrine.
 * Toutes les données proviennent de la base — aucune donnée éditoriale en dur.
 */
class HomeController extends Controller
{
    public function index()
    {
        $listings = MarketplaceListing::published()->latest()->take(4)->get();
        $products = ShopProduct::active()->orderBy('position')->take(4)->get();

        // Aperçu marketplace de la maquette : offres des producteurs + boutique officielle.
        // 'id'/'kind' permettent au bouton "Commander" de pointer vers la vraie ressource
        // (annonce ou produit) plutôt que directement vers /login, pour que
        // redirect()->intended() ramène l'invité dessus une fois connecté/inscrit.
        $offers = $listings->map(fn (MarketplaceListing $l) => [
            'id' => $l->id,
            'kind' => 'listing',
            'label' => $l->type,
            'title' => $l->title,
            'location' => $l->location,
            'price' => $l->price_label,
            'image' => $l->image_path,
            'official' => (bool) $l->is_official,
        ])->concat($products->map(fn (ShopProduct $p) => [
            'id' => $p->id,
            'kind' => 'product',
            'label' => 'Boutique officielle',
            'title' => $p->name,
            'location' => 'Le Roi du Manioc',
            'price' => number_format($p->price, 0, ',', ' ').' FCFA',
            'image' => $p->image_path,
            'official' => true,
        ]))->take(4)->values();

        return view('home', [
            'content' => SiteContent::payload(),
            'formations' => Formation::published()->withCount('lessons')->orderBy('position')->get(),
            'offers' => $offers,
            'testimonials' => Testimonial::published()->get(),
            'awards' => Award::published()->get(),
            // §8.1 : indicateur communauté tiré des vraies données (pas de chiffre en dur).
            'verifiedProducersCount' => ProducerProfile::verified()->active()->count(),
            'completedCollaborationsCount' => Collaboration::where('status', CollaborationStatus::Terminee)->count(),
        ]);
    }
}

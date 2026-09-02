<?php

namespace App\Http\Controllers;

use App\Models\Award;
use App\Models\Event;
use App\Models\Formation;
use App\Models\MarketplaceListing;
use App\Models\ShopProduct;
use App\Models\SiteContent;
use App\Models\Testimonial;

/**
 * Page d'accueil du site vitrine.
 * Toutes les données proviennent de la base — aucune donnée en dur.
 */
class HomeController extends Controller
{
    public function index()
    {
        return view('home', [
            'content' => SiteContent::payload(),
            'formations' => Formation::published()->withCount('lessons')->orderBy('position')->get(),
            'listings' => MarketplaceListing::published()->latest()->take(4)->get(),
            'products' => ShopProduct::active()->orderBy('position')->take(4)->get(),
            'events' => Event::upcoming()->take(3)->get(),
            'testimonials' => Testimonial::published()->get(),
            'awards' => Award::published()->get(),
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceListing;
use App\Models\ShopProduct;
use App\Models\SiteContent;

class MarketplaceController extends Controller
{
    public function index()
    {
        $search = trim((string) request('q', ''));
        $type = trim((string) request('type', ''));

        $listings = MarketplaceListing::published()
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($q2) use ($search) {
                    $q2->where('title', 'like', "%{$search}%")
                        ->orWhere('seller_name', 'like', "%{$search}%");
                });
            })
            ->when($type !== '', fn ($q) => $q->where('type', $type))
            ->latest()->get();

        // Les produits boutique n'ont pas de "type" (Récolte/Bouture/...) — dès qu'un type
        // est filtré, ils sortent naturellement des résultats plutôt que de leur inventer
        // un type qu'ils n'ont pas.
        $products = $type !== ''
            ? collect()
            : ShopProduct::active()
                ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                ->orderBy('position')->get();

        // Même forme unifiée que l'aperçu de l'accueil (HomeController) : offres des
        // producteurs + boutique officielle dans une seule grille, sans limite ici.
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
        ])->concat($products->map(fn (ShopProduct $p) => [
            'id' => $p->id,
            'kind' => 'product',
            'label' => 'Boutique officielle',
            'title' => $p->name,
            'location' => 'Le Roi du Manioc',
            'price' => number_format($p->price, 0, ',', ' ').' FCFA',
            'image' => $p->image_path,
        ]))->values();

        return view('marketplace.index', [
            'offers' => $offers,
            'section' => SiteContent::section('marketplace_section', []),
            'search' => $search,
            'type' => $type,
            // Liste réelle des types en base (jamais codée en dur), parmi les annonces
            // publiées — même principe que les filtres zone/produit de Public\Producers.
            'types' => MarketplaceListing::published()
                ->whereNotNull('type')->where('type', '!=', '')
                ->distinct()->orderBy('type')->pluck('type'),
        ]);
    }
}

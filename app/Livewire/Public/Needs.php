<?php

namespace App\Livewire\Public;

use App\Models\BuyerNeed;
use App\Models\SiteContent;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Liste publique des besoins d'achat ouverts. Aucune coordonnée personnelle de
 * l'acheteur n'est exposée (§8.2) ; la mise en relation (bouton « Répondre via la
 * plateforme ») est amorcée ici mais reste inactive tant que la Phase 5 n'existe pas.
 */
#[Layout('components.public-layout', ['title' => 'Besoins des acheteurs', 'description' => "Les besoins d'achat publiés par les acheteurs du Roi du Manioc : produits recherchés, quantités, zones."])]
class Needs extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $zone = '';

    #[Url]
    public string $product = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingZone(): void
    {
        $this->resetPage();
    }

    public function updatingProduct(): void
    {
        $this->resetPage();
    }

    // Efface tous les filtres d'un coup (lien « Réinitialiser » de la barre latérale).
    public function resetFilters(): void
    {
        $this->reset(['search', 'zone', 'product']);
        $this->resetPage();
    }

    public function render()
    {
        $needs = BuyerNeed::query()
            ->open()
            ->with('buyerProfile')
            ->when($this->search !== '', fn (Builder $q) => $q->where('product_wanted', 'like', "%{$this->search}%"))
            ->when($this->zone !== '', fn (Builder $q) => $q->where('location', 'like', "%{$this->zone}%"))
            ->when($this->product !== '', fn (Builder $q) => $q->where('product_wanted', $this->product))
            ->latest()
            ->paginate(12);

        // Hero de page (même gabarit .page-hero que Formations/Marketplace) : contenu de la
        // section CMS « producteurs_section » (déjà utilisée sur l'accueil et sur /producteurs),
        // plus des chiffres réels, jamais inventés.
        $section = SiteContent::payload()['producteurs_section'] ?? [];
        $openCount = BuyerNeed::open()->count();
        $zonesCount = BuyerNeed::open()->distinct()->count('location');
        // Filtres « zone » et « produit » : listes déroulantes construites depuis les
        // valeurs réellement en base parmi les besoins ouverts (même principe que
        // Public\Producers), jamais une liste codée en dur.
        $zones = BuyerNeed::open()
            ->whereNotNull('location')->where('location', '!=', '')
            ->distinct()->orderBy('location')->pluck('location');
        $products = BuyerNeed::open()
            ->whereNotNull('product_wanted')->where('product_wanted', '!=', '')
            ->distinct()->orderBy('product_wanted')->pluck('product_wanted');

        return view('livewire.public.needs', compact('needs', 'section', 'openCount', 'zonesCount', 'zones', 'products'));
    }
}

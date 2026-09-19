<?php

namespace App\Livewire\Public;

use App\Enums\ActivityType;
use App\Enums\ReviewDirection;
use App\Models\CropOffer;
use App\Models\ProducerProfile;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Catalogue public des producteurs ayant au moins une offre publiée (vérifiés ou non —
 * le badge « vérifié » distingue simplement ceux qui le sont). Aucune coordonnée
 * personnelle n'est exposée ici (§8.2) ; pas de fiche détaillée ni de mise en relation
 * (Phase 5), uniquement un annuaire filtrable.
 */
#[Layout('components.public-layout', ['title' => 'Producteurs', 'description' => 'Le catalogue des producteurs agricoles et éleveurs vérifiés du Roi du Manioc : récoltes, boutures, produits transformés, intrants et élevage.'])]
class Producers extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $zone = '';

    #[Url]
    public string $product = '';

    #[Url]
    public string $activityType = '';

    #[Url]
    public bool $verifiedOnly = false;

    #[Url]
    public bool $availableNow = false;

    #[Url]
    public string $minRating = '';

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

    public function updatingActivityType(): void
    {
        $this->resetPage();
    }

    public function updatingVerifiedOnly(): void
    {
        $this->resetPage();
    }

    public function updatingAvailableNow(): void
    {
        $this->resetPage();
    }

    public function updatingMinRating(): void
    {
        $this->resetPage();
    }

    // Efface tous les filtres d'un coup (lien « Réinitialiser » de la barre latérale) — un
    // seul aller-retour plutôt que d'enchaîner un $set par propriété depuis la vue.
    public function resetFilters(): void
    {
        $this->reset(['search', 'zone', 'product', 'activityType', 'verifiedOnly', 'availableNow', 'minRating']);
        $this->resetPage();
    }

    public function render()
    {
        $producers = ProducerProfile::query()
            ->active()
            ->whereHas('cropOffers', fn (Builder $q) => $q->published())
            ->with(['cropOffers' => fn ($q) => $q->published()->latest()->limit(3)])
            ->withAvg('receivedReviews as avg_rating', 'rating')
            ->withCount('receivedReviews as reviews_count')
            ->when($this->search !== '', function (Builder $q) {
                $q->where(function (Builder $q2) {
                    $q2->where('business_name', 'like', "%{$this->search}%")
                        ->orWhereHas('cropOffers', function (Builder $q3) {
                            $q3->published()->where(function (Builder $q4) {
                                $q4->where('product_name', 'like', "%{$this->search}%")
                                    ->orWhere('variety', 'like', "%{$this->search}%");
                            });
                        });
                });
            })
            ->when($this->zone !== '', fn (Builder $q) => $q->where('zone', 'like', "%{$this->zone}%"))
            ->when($this->product !== '', function (Builder $q) {
                $q->whereHas('cropOffers', fn (Builder $q3) => $q3->published()->where('product_name', $this->product));
            })
            ->when($this->activityType !== '', fn (Builder $q) => $q->where('activity_type', $this->activityType))
            ->when($this->verifiedOnly, fn (Builder $q) => $q->verified())
            ->when($this->availableNow, function (Builder $q) {
                $q->whereHas('cropOffers', function (Builder $q3) {
                    $q3->published()->where(function (Builder $q4) {
                        $q4->whereNull('available_from')->orWhereDate('available_from', '<=', now());
                    });
                });
            })
            // Sous-requête corrélée plutôt que having() sur l'alias withAvg : having() sur une
            // colonne non-agrégée sans group by fonctionne en MySQL mais pas en SQLite (utilisé
            // par les tests). CAST(? AS DECIMAL(4,2)) est nécessaire aussi : un float PHP lié
            // tel quel échoue silencieusement la comparaison sous SQLite (le sous-select renvoie
            // 0 ligne même quand la moyenne dépasse le seuil), et CAST(? AS DECIMAL) sans
            // précision tronque .5 en l'arrondissant côté MySQL — cette forme précise est la
            // seule qui se comporte correctement identiquement sur les deux moteurs.
            ->when($this->minRating !== '', function (Builder $q) {
                $q->whereRaw(
                    '(select avg(rating) from reviews where reviews.ratee_id = producer_profiles.user_id and reviews.direction = ?) >= CAST(? AS DECIMAL(4,2))',
                    [ReviewDirection::AcheteurVersProducteur->value, (float) $this->minRating],
                );
            })
            ->orderByDesc('verified_at')
            ->orderBy('business_name')
            ->paginate(12);

        return view('livewire.public.producers', [
            'producers' => $producers,
            'activityTypes' => ActivityType::cases(),
            // Filtres « zone » et « produit » : de vraies listes déroulantes plutôt que du
            // texte libre, construites depuis les valeurs réellement en base (jamais une
            // liste codée en dur) — uniquement parmi les producteurs actifs ayant au moins
            // une offre publiée, cohérent avec le reste du catalogue.
            'zones' => ProducerProfile::query()
                ->active()
                ->whereHas('cropOffers', fn (Builder $q) => $q->published())
                ->whereNotNull('zone')->where('zone', '!=', '')
                ->distinct()->orderBy('zone')->pluck('zone'),
            'products' => CropOffer::published()
                ->whereNotNull('product_name')->where('product_name', '!=', '')
                ->distinct()->orderBy('product_name')->pluck('product_name'),
            // Favoris (§11) : ouvert à tout visiteur connecté, cf. FavoriteController — pas
            // seulement un acheteur déjà activé. Un seul aller-retour pour toute la page,
            // plutôt qu'une requête par carte.
            'favoriteIds' => auth()->check()
                ? auth()->user()->favoriteProducers()->pluck('producer_profiles.id')->all()
                : [],
        ]);
    }
}

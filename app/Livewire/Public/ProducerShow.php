<?php

namespace App\Livewire\Public;

use App\Models\ProducerProfile;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Fiche publique d'un producteur : profil + TOUTES ses offres publiées (contrairement à la
 * carte du catalogue /producteurs, limitée aux 3 plus récentes). Aucune coordonnée
 * personnelle (§8.2, même garde de visibilité que Public\Producers/CropOffer::scopePublished).
 */
class ProducerShow extends Component
{
    use WithPagination;

    public ProducerProfile $producerProfile;

    public function mount(ProducerProfile $producerProfile): void
    {
        // Même garde que le catalogue : compte propriétaire non suspendu. Pas d'exigence
        // d'avoir au moins une offre publiée — un producteur temporairement sans offre
        // disponible garde une fiche consultable plutôt qu'un 404.
        abort_unless(
            ProducerProfile::active()->whereKey($producerProfile->id)->exists(),
            404
        );

        $this->producerProfile = $producerProfile;
    }

    public function render()
    {
        $offers = $this->producerProfile->cropOffers()
            ->published()
            ->with('photos')
            ->latest()
            ->paginate(9);

        return view('livewire.public.producer-show', [
            'offers' => $offers,
            'averageRating' => $this->producerProfile->averageRating(),
            'reviewsCount' => $this->producerProfile->reviewsCount(),
            'isFavorite' => auth()->check()
                ? auth()->user()->favoriteProducers()->where('producer_profiles.id', $this->producerProfile->id)->exists()
                : false,
        ])->layout('components.public-layout', [
            // Titre dynamique (dépend du producteur chargé) : #[Layout(...)] est une valeur
            // figée au niveau de la classe, on passe donc par ->layout() dans render() —
            // même mécanisme que Account\Settings::render() (layout conditionnel).
            'title' => $this->producerProfile->business_name,
            'description' => "La fiche de {$this->producerProfile->business_name} sur Le Roi du Manioc : offres publiées, zone, note.",
        ]);
    }
}

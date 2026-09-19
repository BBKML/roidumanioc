<?php

namespace App\Livewire\Admin;

use App\Enums\CropOfferStatus;
use App\Models\CropOffer;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Modération des offres producteur (Phase 10, §25/§37). Contrairement à
 * Admin\MarketplaceModeration, une offre n'a pas de file d'attente à valider (le
 * producteur publie directement, cf. CropOfferPolicy — pas de statut `en_attente`) : le
 * seul pouvoir propre à l'admin ici est de retirer une offre problématique (`archivee`,
 * même sémantique que l'auto-archivage producteur) et de la restaurer si besoin — mise à
 * jour directe du statut (`->update()`), pas de méthode dédiée sur le modèle : CropOffer
 * n'a pas la philosophie « transition idempotente » des state machines (ConnectionRequest/
 * Collaboration/Payment/ProducerProfile), même choix que MarketplaceModeration.
 */
#[Layout('components.layouts.admin')]
class CropOffers extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $filter = 'tous';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilter(): void
    {
        $this->resetPage();
    }

    /** Recherche/filtre sans résultat (audit architecture) : un vrai bouton pour repartir de zéro. */
    public function resetFilters(): void
    {
        $this->reset('search', 'filter');
    }

    public function archive(CropOffer $offer): void
    {
        $offer->update(['status' => CropOfferStatus::Archivee]);
        $this->dispatch('notify', message: 'Offre archivée.');
    }

    public function restore(CropOffer $offer): void
    {
        $offer->update(['status' => CropOfferStatus::Publiee]);
        $this->dispatch('notify', message: 'Offre republiée.');
    }

    public function render()
    {
        $offers = CropOffer::query()
            ->with('producerProfile')
            ->when($this->filter !== 'tous', fn ($q) => $q->where('status', $this->filter))
            ->when($this->search !== '', fn ($q) => $q->where(
                fn ($q2) => $q2->where('product_name', 'like', "%{$this->search}%")
                    ->orWhereHas('producerProfile', fn ($q3) => $q3->where('business_name', 'like', "%{$this->search}%"))
            ))
            ->latest()
            ->paginate(20);

        return view('livewire.admin.crop-offers', [
            'offers' => $offers,
            'statuses' => CropOfferStatus::cases(),
        ]);
    }
}

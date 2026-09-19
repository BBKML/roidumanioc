<?php

namespace App\Livewire\Admin;

use App\Enums\BuyerNeedStatus;
use App\Models\BuyerNeed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Modération des besoins acheteur (Phase 10, §25/§37) — symétrique d'Admin\CropOffers :
 * pas de file de validation (l'acheteur publie directement), le seul pouvoir propre à
 * l'admin est de fermer un besoin problématique (`ferme`) et de le rouvrir. Mise à jour
 * directe du statut, même choix qu'Admin\CropOffers/MarketplaceModeration.
 */
#[Layout('components.layouts.admin')]
class BuyerNeeds extends Component
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

    public function close(BuyerNeed $need): void
    {
        $need->update(['status' => BuyerNeedStatus::Ferme]);
        $this->dispatch('notify', message: 'Besoin fermé.');
    }

    public function reopen(BuyerNeed $need): void
    {
        $need->update(['status' => BuyerNeedStatus::Ouvert]);
        $this->dispatch('notify', message: 'Besoin réouvert.');
    }

    public function render()
    {
        $needs = BuyerNeed::query()
            ->with('buyerProfile')
            ->when($this->filter !== 'tous', fn ($q) => $q->where('status', $this->filter))
            ->when($this->search !== '', fn ($q) => $q->where(
                fn ($q2) => $q2->where('product_wanted', 'like', "%{$this->search}%")
                    ->orWhereHas('buyerProfile', fn ($q3) => $q3->where('company_name', 'like', "%{$this->search}%"))
            ))
            ->latest()
            ->paginate(20);

        return view('livewire.admin.buyer-needs', [
            'needs' => $needs,
            'statuses' => BuyerNeedStatus::cases(),
        ]);
    }
}

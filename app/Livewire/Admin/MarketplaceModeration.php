<?php

namespace App\Livewire\Admin;

use App\Enums\ListingStatus;
use App\Models\MarketplaceListing;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class MarketplaceModeration extends Component
{
    use WithPagination;

    #[Url]
    public string $filter = 'en_attente';

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
        // Pagination ajoutée à cet écran (audit UX, Phase 2) : même réflexe que
        // CropOffers/BuyerNeeds — changer de filtre revient à la première page.
        $this->resetPage();
    }

    public function approve(MarketplaceListing $listing): void
    {
        $listing->update(['status' => ListingStatus::Validee]);
        $this->dispatch('notify', message: 'Annonce publiée.');
    }

    public function reject(MarketplaceListing $listing): void
    {
        $listing->update(['status' => ListingStatus::Refuse]);
        $this->dispatch('notify', message: 'Annonce refusée.');
    }

    public function unpublish(MarketplaceListing $listing): void
    {
        $listing->update(['status' => ListingStatus::EnAttente]);
        $this->dispatch('notify', message: 'Annonce retirée — repassée en attente.');
    }

    public function delete(MarketplaceListing $listing): void
    {
        $listing->delete();
        $this->dispatch('notify', message: 'Annonce supprimée.');
    }

    public function render()
    {
        $query = MarketplaceListing::with('seller')->latest();

        if (in_array($this->filter, ['en_attente', 'validee', 'refuse'], true)) {
            $query->where('status', $this->filter);
        }

        return view('livewire.admin.marketplace-moderation', [
            'listings' => $query->paginate(20),
            'counts' => [
                'en_attente' => MarketplaceListing::where('status', 'en_attente')->count(),
                'validee' => MarketplaceListing::where('status', 'validee')->count(),
                'refuse' => MarketplaceListing::where('status', 'refuse')->count(),
            ],
        ]);
    }
}

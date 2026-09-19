<?php

namespace App\Livewire\Buyer;

use App\Models\ProducerProfile;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Liste des producteurs favoris de l'acheteur (§11). Non paginée — un utilisateur en a
 * toujours un nombre borné, même logique que Formations/Boutique/Événements.
 */
#[Layout('components.layouts.learner')]
class Favorites extends Component
{
    public function unfavorite(ProducerProfile $producerProfile): void
    {
        Auth::user()->favoriteProducers()->detach($producerProfile->id);

        $this->dispatch('notify', message: 'Retiré des favoris.');
    }

    public function render()
    {
        $favorites = Auth::user()->favoriteProducers()
            ->withAvg('receivedReviews as avg_rating', 'rating')
            ->withCount('receivedReviews as reviews_count')
            ->with(['cropOffers' => fn ($q) => $q->published()->latest()->limit(3)])
            ->orderByDesc('favorites.created_at')
            ->get();

        return view('livewire.buyer.favorites', compact('favorites'));
    }
}

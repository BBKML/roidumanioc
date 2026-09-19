<?php

namespace App\Livewire\Producer;

use App\Models\CropOffer;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.learner')]
class Offers extends Component
{
    use WithPagination;

    public function toggleAvailability(CropOffer $offer): void
    {
        $this->authorize('update', $offer);

        $offer->update(['is_available' => ! $offer->is_available]);
    }

    public function delete(CropOffer $offer): void
    {
        $this->authorize('delete', $offer);

        $offer->delete();

        $this->dispatch('notify', message: 'Offre supprimée.');
    }

    public function render()
    {
        $offers = Auth::user()->producerProfile->cropOffers()
            ->with('photos')
            ->latest()
            ->paginate(9);

        return view('livewire.producer.offers', compact('offers'));
    }
}

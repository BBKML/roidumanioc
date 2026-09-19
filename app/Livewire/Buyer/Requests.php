<?php

namespace App\Livewire\Buyer;

use App\Enums\ConnectionRequestStatus;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Boîte de réception des demandes de mise en relation reçues/envoyées par l'acheteur.
 */
#[Layout('components.layouts.learner')]
class Requests extends Component
{
    use WithPagination;

    #[Url]
    public string $filter = 'tous';

    public function updatingFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $requests = Auth::user()->buyerProfile->connectionRequests()
            ->with(['cropOffer', 'buyerNeed', 'producerProfile'])
            ->when($this->filter !== 'tous', fn ($q) => $q->where('status', $this->filter))
            ->latest()
            ->paginate(15);

        return view('livewire.buyer.requests', [
            'requests' => $requests,
            'statuses' => ConnectionRequestStatus::cases(),
        ]);
    }
}

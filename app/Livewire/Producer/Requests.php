<?php

namespace App\Livewire\Producer;

use App\Enums\ConnectionRequestStatus;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Boîte de réception des demandes de mise en relation reçues/envoyées par le producteur.
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
        $requests = Auth::user()->producerProfile->connectionRequests()
            ->with(['cropOffer', 'buyerNeed', 'buyerProfile'])
            ->when($this->filter !== 'tous', fn ($q) => $q->where('status', $this->filter))
            ->latest()
            ->paginate(15);

        return view('livewire.producer.requests', [
            'requests' => $requests,
            'statuses' => ConnectionRequestStatus::cases(),
        ]);
    }
}

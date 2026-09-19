<?php

namespace App\Livewire\Admin;

use App\Models\Collaboration;
use App\Models\ProducerProfile;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/** Fiche détail d'un producteur (Phase 10, §25/§37) — vue admin, lecture + vérification. */
#[Layout('components.layouts.admin')]
class ProducerShow extends Component
{
    use WithPagination;

    public ProducerProfile $producerProfile;

    public function mount(ProducerProfile $producerProfile): void
    {
        $this->producerProfile = $producerProfile;
    }

    public function verify(): void
    {
        if ($this->producerProfile->verify(Auth::user())) {
            $this->dispatch('notify', message: 'Producteur vérifié.');
        }
    }

    public function reject(): void
    {
        if ($this->producerProfile->rejectVerification()) {
            $this->dispatch('notify', message: 'Vérification retirée.');
        }
    }

    public function render()
    {
        $this->producerProfile->loadMissing(['user', 'verifier']);

        return view('livewire.admin.producer-show', [
            'offers' => $this->producerProfile->cropOffers()->latest()->get(),
            // Paginées plutôt que limitées à 10 sans échappatoire (audit UX, Phase 2) —
            // gabarit de pagination existant réutilisé, `pageName` distinct car les deux
            // paginateurs coexistent sur la même page.
            'connectionRequests' => $this->producerProfile->connectionRequests()
                ->with(['cropOffer', 'buyerNeed', 'buyerProfile'])->latest()
                ->paginate(10, ['*'], 'requestsPage'),
            'collaborations' => Collaboration::where('producer_profile_id', $this->producerProfile->id)
                ->with('buyerProfile')->latest()
                ->paginate(10, ['*'], 'collabsPage'),
            'averageRating' => $this->producerProfile->averageRating(),
            'reviewsCount' => $this->producerProfile->reviewsCount(),
        ]);
    }
}

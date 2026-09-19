<?php

namespace App\Livewire\Admin;

use App\Enums\ConnectionRequestStatus;
use App\Models\ConnectionRequest;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Supervision passive des demandes de mise en relation (Phase 10, §25/§37) — lecture
 * seule, aucune action de mutation ici : le détail (App\Livewire\Connect\Show) est déjà
 * ouvert à l'admin via le contournement générique de `Gate::before` sur l'ability `view`
 * (ConnectionRequestPolicy::view délègue à `isParty()`, mais un admin passe cette
 * vérification quand même — seules les transitions de parties en sont exclues).
 */
#[Layout('components.layouts.admin')]
class ConnectionRequests extends Component
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
        $requests = ConnectionRequest::query()
            ->with(['cropOffer', 'buyerNeed', 'producerProfile', 'buyerProfile'])
            ->when($this->filter !== 'tous', fn ($q) => $q->where('status', $this->filter))
            ->latest()
            ->paginate(25);

        return view('livewire.admin.connection-requests', [
            'requests' => $requests,
            'statuses' => ConnectionRequestStatus::cases(),
        ]);
    }
}

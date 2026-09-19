<?php

namespace App\Livewire\Admin;

use App\Enums\CropOrderStatus;
use App\Models\CropOrder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Supervision des commandes producteur↔acheteur (§10) — lecture seule, aucune action de
 * mutation ici : le détail (App\Livewire\CropOrder\Show) est déjà ouvert à l'admin via le
 * contournement générique de `Gate::before` sur l'ability `view`. La gestion de l'aide à
 * la livraison a son propre écran dédié (App\Livewire\Admin\DeliveryAssists).
 */
#[Layout('components.layouts.admin')]
class CropOrders extends Component
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
        $orders = CropOrder::query()
            ->with(['producerProfile', 'buyerProfile'])
            ->when($this->filter !== 'tous', fn ($q) => $q->where('status', $this->filter))
            ->latest()
            ->paginate(25);

        return view('livewire.admin.crop-orders', [
            'orders' => $orders,
            'statuses' => CropOrderStatus::cases(),
        ]);
    }
}

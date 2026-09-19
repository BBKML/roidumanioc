<?php

namespace App\Livewire\Admin;

use App\Enums\DeliveryAssistStatus;
use App\Models\CropOrder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * « Aide livraison » (§9/§10) — véritable espace de gestion des livraisons, seul pouvoir
 * propre de l'admin sur App\Models\CropOrder (markDeliveryAssistStep, symétrique de
 * Collaboration::markDisputed()). N'apparaît qu'une fois la commande confirmée par les
 * deux parties (App\Models\CropOrder::requestDeliveryAssistance()).
 */
#[Layout('components.layouts.admin')]
class DeliveryAssists extends Component
{
    use WithPagination;

    #[Url]
    public string $filter = 'en_cours';

    public function updatingFilter(): void
    {
        $this->resetPage();
    }

    public function markStep(int $cropOrderId, string $to): void
    {
        $cropOrder = CropOrder::with('deliveryAssist')->findOrFail($cropOrderId);
        $status = DeliveryAssistStatus::from($to);

        $this->authorize('markDeliveryAssistStep', [$cropOrder, $status]);

        if ($cropOrder->markDeliveryAssistStep(Auth::user(), $status)) {
            $this->dispatch('notify', message: 'Statut de livraison mis à jour.');
        }
    }

    public function render()
    {
        $query = CropOrder::query()
            ->whereHas('deliveryAssist')
            ->with(['producerProfile', 'buyerProfile', 'deliveryAssist'])
            ->latest('delivery_assist_requested_at');

        if ($this->filter === 'en_cours') {
            $query->whereHas('deliveryAssist', fn ($q) => $q->whereNotIn('status', ['livree', 'annulee']));
        } elseif ($this->filter !== 'tous') {
            $query->whereHas('deliveryAssist', fn ($q) => $q->where('status', $this->filter));
        }

        return view('livewire.admin.delivery-assists', [
            'orders' => $query->paginate(25),
            'statuses' => DeliveryAssistStatus::cases(),
        ]);
    }
}

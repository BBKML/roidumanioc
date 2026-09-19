<?php

namespace App\Livewire\Learner;

use App\Enums\CropOrderStatus;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * « Mes commandes » — page UNIQUE pour tout ce qui s'appelle « commande » sur un compte :
 * achats boutique/annonces (Order/Payment, système historique) + commandes producteur
 * structurées (CropOrder, §1-§10). Un compte voyait auparavant DEUX liens différents
 * appelés « commande(s) » (« Mes commandes » et « Mes commandes produits »/« Commandes
 * reçues ») — fusionnés ici en sections sur une seule page, même principe que le tableau
 * de bord unique (Phase 15/16, cf. App\Livewire\Learner\Dashboard). Les anciens composants
 * séparés App\Livewire\Producer\CropOrders / Buyer\CropOrders sont supprimés.
 */
#[Layout('components.layouts.learner')]
class Orders extends Component
{
    use WithPagination;

    #[Url]
    public string $producerOrderFilter = 'nouvelles';

    #[Url]
    public string $buyerOrderFilter = 'tous';

    public function updatingProducerOrderFilter(): void
    {
        $this->resetPage('producerOrdersPage');
    }

    public function updatingBuyerOrderFilter(): void
    {
        $this->resetPage('buyerOrdersPage');
    }

    private function producerStatusesForFilter(): ?array
    {
        return match ($this->producerOrderFilter) {
            'nouvelles' => [CropOrderStatus::EnAttenteProducteur],
            'negociation' => [CropOrderStatus::Acceptee, CropOrderStatus::EnAttenteValidationAdmin, CropOrderStatus::NegociationLivraison],
            'confirmees' => [CropOrderStatus::CommandeConfirmee],
            'livraison' => [CropOrderStatus::AideLivraison, CropOrderStatus::LivraisonEnPreparation, CropOrderStatus::LivraisonEnCours, CropOrderStatus::LivraisonAutoOrganisee],
            'terminees' => [CropOrderStatus::Livree],
            'refusees' => [CropOrderStatus::Refusee, CropOrderStatus::Annulee],
            default => null,
        };
    }

    public function render()
    {
        $user = Auth::user();
        $isProducer = $user->isProducer();
        $isBuyer = $user->isBuyer();

        $data = [
            'payments' => $user->payments()->with('enrollment.formation')->latest('submitted_at')->get(),
            'orders' => $user->orders()->latest('ordered_at')->get(),
            'isProducer' => $isProducer,
            'isBuyer' => $isBuyer,
        ];

        if ($isProducer) {
            $producerStatuses = $this->producerStatusesForFilter();

            $data['producerOrders'] = $user->producerProfile->cropOrders()
                ->with(['buyerProfile', 'cropOffer'])
                ->when($producerStatuses, fn ($q) => $q->whereIn('status', $producerStatuses))
                ->latest()
                ->paginate(10, pageName: 'producerOrdersPage');
        }

        if ($isBuyer) {
            $data['buyerOrders'] = $user->buyerProfile->cropOrders()
                ->with(['producerProfile', 'cropOffer'])
                ->when($this->buyerOrderFilter !== 'tous', fn ($q) => $q->where('status', $this->buyerOrderFilter))
                ->latest()
                ->paginate(10, pageName: 'buyerOrdersPage');
            $data['buyerOrderStatuses'] = CropOrderStatus::cases();
        }

        return view('livewire.learner.orders', $data);
    }
}

<?php

namespace App\Livewire\CropOrder;

use App\Enums\CropOrderStatus;
use App\Models\CropOrder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Écran partagé du parcours de commande structuré (§1-§10) — stepper vertical + actions
 * disponibles pour l'utilisateur courant + historique des frais de livraison. Délibérément
 * SANS discussion libre (voir App\Livewire\Connect\Show pour le système de chat, distinct).
 */
#[Layout('components.layouts.learner')]
class Show extends Component
{
    public CropOrder $cropOrder;

    public string $refusalReason = '';

    public bool $showRefuseForm = false;

    public string $productPriceTotal = '';

    public string $deliveryFeeProposed = '';

    public string $deliveryConditionsNote = '';

    public string $proposedAmount = '';

    public string $proposalNote = '';

    public string $adminReviewNote = '';

    public bool $showRejectForm = false;

    public function mount(CropOrder $cropOrder): void
    {
        $this->authorize('view', $cropOrder);

        $this->cropOrder = $cropOrder;
    }

    public function accept(): void
    {
        $this->authorize('accept', $this->cropOrder);

        if ($this->cropOrder->accept(Auth::user())) {
            $this->dispatch('notify', message: 'Commande acceptée.');
        }

        $this->cropOrder->refresh();
    }

    public function refuse(): void
    {
        $this->authorize('refuse', $this->cropOrder);

        $this->validate(['refusalReason' => ['nullable', 'string', 'max:500']]);

        if ($this->cropOrder->refuse(Auth::user(), $this->refusalReason ?: null)) {
            $this->reset('refusalReason');
            $this->showRefuseForm = false;
            $this->dispatch('notify', message: 'Commande refusée.');
        }

        $this->cropOrder->refresh();
    }

    public function cancel(): void
    {
        $this->authorize('cancel', $this->cropOrder);

        if ($this->cropOrder->cancel(Auth::user())) {
            $this->dispatch('notify', message: 'Commande annulée.');
        }

        $this->cropOrder->refresh();
    }

    /** Le producteur soumet ses conditions à l'admin — l'acheteur ne voit encore rien. */
    public function submitDeliveryConditions(): void
    {
        $this->authorize('submitDeliveryConditions', $this->cropOrder);

        $validated = $this->validate([
            'productPriceTotal' => ['required', 'integer', 'min:1'],
            'deliveryFeeProposed' => ['required', 'integer', 'min:0'],
            'deliveryConditionsNote' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($this->cropOrder->submitDeliveryConditionsForReview(
            Auth::user(),
            (int) $validated['productPriceTotal'],
            (int) $validated['deliveryFeeProposed'],
            $validated['deliveryConditionsNote'] ?: null,
        )) {
            $this->dispatch('notify', message: "Conditions envoyées à l'administration pour validation.");
        }

        $this->cropOrder->refresh();
    }

    public function approveDeliveryConditions(): void
    {
        $this->authorize('approveDeliveryConditions', $this->cropOrder);

        if ($this->cropOrder->approveDeliveryConditions(Auth::user(), $this->adminReviewNote ?: null)) {
            $this->reset('adminReviewNote');
            $this->dispatch('notify', message: "Conditions validées, envoyées à l'acheteur.");
        }

        $this->cropOrder->refresh();
    }

    public function rejectDeliveryConditions(): void
    {
        $this->authorize('rejectDeliveryConditions', $this->cropOrder);

        $this->validate(['adminReviewNote' => ['required', 'string', 'max:1000']]);

        if ($this->cropOrder->rejectDeliveryConditions(Auth::user(), $this->adminReviewNote)) {
            $this->reset('adminReviewNote');
            $this->showRejectForm = false;
            $this->dispatch('notify', message: 'Conditions renvoyées au producteur.');
        }

        $this->cropOrder->refresh();
    }

    public function proposeDeliveryFee(): void
    {
        $this->authorize('proposeDeliveryFee', $this->cropOrder);

        $validated = $this->validate([
            'proposedAmount' => ['required', 'integer', 'min:0'],
            'proposalNote' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($this->cropOrder->proposeDeliveryFee(Auth::user(), (int) $validated['proposedAmount'], $validated['proposalNote'] ?: null)) {
            $this->reset('proposedAmount', 'proposalNote');
            $this->dispatch('notify', message: 'Contre-proposition envoyée.');
        }

        $this->cropOrder->refresh();
    }

    public function acceptDeliveryFee(): void
    {
        $this->authorize('acceptDeliveryFee', $this->cropOrder);

        if ($this->cropOrder->acceptDeliveryFee(Auth::user())) {
            $this->dispatch('notify', message: 'Commande confirmée !');
        }

        $this->cropOrder->refresh();
    }

    public function requestDeliveryAssistance(): void
    {
        $this->authorize('requestDeliveryAssistance', $this->cropOrder);

        if ($this->cropOrder->requestDeliveryAssistance(Auth::user())) {
            $this->dispatch('notify', message: "Aide à la livraison demandée.");
        }

        $this->cropOrder->refresh();
    }

    /** `$mode` : 'acheteur' (le client récupère / a son propre livreur) ou 'producteur' (livraison par le producteur). */
    public function declareSelfArrangedDelivery(string $mode): void
    {
        $this->authorize('declareSelfArrangedDelivery', [$this->cropOrder, $mode]);

        if ($this->cropOrder->declareSelfArrangedDelivery(Auth::user(), $mode)) {
            $this->dispatch('notify', message: 'Livraison auto-organisée.');
        }

        $this->cropOrder->refresh();
    }

    public function confirmSelfArrangedDelivery(): void
    {
        $this->authorize('confirmSelfArrangedDelivery', $this->cropOrder);

        if ($this->cropOrder->confirmSelfArrangedDelivery(Auth::user())) {
            $this->dispatch('notify', message: 'Livraison confirmée — commande terminée !');
        }

        $this->cropOrder->refresh();
    }

    public function cancelSelfArrangedDelivery(): void
    {
        $this->authorize('cancelSelfArrangedDelivery', $this->cropOrder);

        if ($this->cropOrder->cancelSelfArrangedDelivery(Auth::user())) {
            $this->dispatch('notify', message: 'Livraison auto-organisée annulée.');
        }

        $this->cropOrder->refresh();
    }

    public function render()
    {
        $user = Auth::user();
        $o = $this->cropOrder;
        $happyPath = CropOrderStatus::happyPath();

        // Livraison auto-organisée : les étapes assistées par l'admin ne se produisent
        // jamais dans ce cas — les retirer du stepper plutôt que de les afficher "acquises"
        // à tort une fois `livree` atteint (furthestHappyPathIndex sauterait directement à
        // la fin sinon).
        if ($o->self_arranged_mode) {
            $happyPath = array_values(array_filter($happyPath, fn (CropOrderStatus $s) => ! in_array($s, [
                CropOrderStatus::AideLivraison, CropOrderStatus::LivraisonEnPreparation, CropOrderStatus::LivraisonEnCours,
            ], true)));
        }

        return view('livewire.crop-order.show', [
            'steps' => $happyPath,
            'furthestIndex' => $o->furthestHappyPathIndex($happyPath),
            'currentIndex' => in_array($o->status, $happyPath, true) ? array_search($o->status, $happyPath, true) : null,
            'deliveryProposals' => $o->deliveryProposals()->latest('id')->get(),
            'activities' => $o->activities()->latest()->get(),
            'canAccept' => $o->canBeAcceptedBy($user),
            'canRefuse' => $o->canBeRefusedBy($user),
            'canCancel' => $o->canBeCancelledBy($user),
            'canSubmitDeliveryConditions' => $o->canSubmitDeliveryConditionsBy($user),
            'canReviewDeliveryConditions' => $o->canReviewDeliveryConditionsBy($user),
            'canProposeDeliveryFee' => $o->canProposeDeliveryFeeBy($user),
            'canAcceptDeliveryFee' => $o->canAcceptDeliveryFeeBy($user),
            'canRequestDeliveryAssistance' => $o->canRequestDeliveryAssistanceBy($user),
            'canDeclareBuyerSelfArranged' => $o->canDeclareSelfArrangedDeliveryBy($user, 'acheteur'),
            'canDeclareProducerSelfArranged' => $o->canDeclareSelfArrangedDeliveryBy($user, 'producteur'),
            'canConfirmSelfArrangedDelivery' => $o->canConfirmSelfArrangedDeliveryBy($user),
            'canCancelSelfArrangedDelivery' => $o->canCancelSelfArrangedDeliveryBy($user),
            'isViewerAdmin' => $user->isAdmin() && ! $o->isParty($user),
        ]);
    }
}

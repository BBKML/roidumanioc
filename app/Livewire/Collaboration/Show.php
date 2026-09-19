<?php

namespace App\Livewire\Collaboration;

use App\Enums\CollaborationDeliveryStatus;
use App\Enums\CollaborationStatus;
use App\Models\Collaboration;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * « Ma collaboration » — 3 blocs (Accord / Paiement / Livraison), même langage visuel
 * de stepper que Connect\Show (Phase 5).
 */
#[Layout('components.layouts.learner')]
class Show extends Component
{
    public Collaboration $collaboration;

    public string $amountDeclared = '';

    public string $method = '';

    public string $paymentNote = '';

    public string $contestReason = '';

    public bool $showContestForm = false;

    public string $disputeReason = '';

    public bool $showDisputeForm = false;

    public function mount(Collaboration $collaboration): void
    {
        $this->authorize('view', $collaboration);

        $this->collaboration = $collaboration;
    }

    public function declarePayment(): void
    {
        $this->authorize('declarePayment', $this->collaboration);

        $this->validate([
            'amountDeclared' => ['required', 'integer', 'min:1'],
            'method' => ['required', 'string', 'max:60'],
            'paymentNote' => ['nullable', 'string', 'max:500'],
        ]);

        if ($this->collaboration->declarePayment(Auth::user(), (int) $this->amountDeclared, $this->method, $this->paymentNote ?: null)) {
            $this->reset('amountDeclared', 'method', 'paymentNote');
            $this->dispatch('notify', message: 'Paiement déclaré.');
        }

        $this->collaboration->refresh();
    }

    public function confirmPayment(): void
    {
        $this->authorize('confirmPayment', $this->collaboration);

        if ($this->collaboration->confirmPayment(Auth::user())) {
            $this->dispatch('notify', message: 'Paiement confirmé.');
        }

        $this->collaboration->refresh();
    }

    public function contestPayment(): void
    {
        $this->authorize('contestPayment', $this->collaboration);

        $this->validate(['contestReason' => ['required', 'string', 'max:500']]);

        if ($this->collaboration->contestPayment(Auth::user(), $this->contestReason)) {
            $this->reset('contestReason');
            $this->showContestForm = false;
            $this->dispatch('notify', message: 'Paiement contesté — l\'acheteur peut redéclarer.');
        }

        $this->collaboration->refresh();
    }

    public function markDeliveryStep(string $to): void
    {
        $status = CollaborationDeliveryStatus::from($to);

        $this->authorize('markDeliveryStep', [$this->collaboration, $status]);

        if ($this->collaboration->markDeliveryStep(Auth::user(), $status)) {
            $this->dispatch('notify', message: 'Étape de livraison mise à jour.');
        }

        $this->collaboration->refresh();
    }

    public function cancel(): void
    {
        $this->authorize('cancel', $this->collaboration);

        if ($this->collaboration->cancel(Auth::user())) {
            $this->dispatch('notify', message: 'Collaboration annulée.');
        }

        $this->collaboration->refresh();
    }

    public function markDisputed(): void
    {
        $this->authorize('markDisputed', $this->collaboration);

        $this->validate(['disputeReason' => ['required', 'string', 'max:500']]);

        if ($this->collaboration->markDisputed(Auth::user(), $this->disputeReason)) {
            $this->reset('disputeReason');
            $this->showDisputeForm = false;
            $this->dispatch('notify', message: 'Litige signalé.');
        }

        $this->collaboration->refresh();
    }

    public function render()
    {
        $user = Auth::user();
        $c = $this->collaboration;

        return view('livewire.collaboration.show', [
            'steps' => CollaborationStatus::happyPath(),
            'currentIndex' => in_array($c->status, CollaborationStatus::happyPath(), true)
                ? array_search($c->status, CollaborationStatus::happyPath(), true)
                : null,
            'deliverySteps' => CollaborationDeliveryStatus::steps(),
            'payments' => $c->payments()->latest()->get(),
            'latestPayment' => $c->payments()->latest()->first(),
            'canDeclarePayment' => $c->canDeclarePaymentBy($user),
            'canRespondToPayment' => $c->canRespondToPaymentBy($user),
            'canMarkEnCours' => $c->canMarkDeliveryStepBy($user, CollaborationDeliveryStatus::EnCours),
            'canMarkEffectuee' => $c->canMarkDeliveryStepBy($user, CollaborationDeliveryStatus::Effectuee),
            'canMarkReceptionnee' => $c->canMarkDeliveryStepBy($user, CollaborationDeliveryStatus::Receptionnee),
            'canCancel' => $c->canBeCancelledBy($user),
            'canDispute' => $c->canBeDisputedBy($user),
            'isViewerAdmin' => $user->isAdmin() && ! $c->isParty($user),
        ]);
    }
}

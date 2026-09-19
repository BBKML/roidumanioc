<?php

namespace App\Livewire\Admin;

use App\Mail\PaymentConfirmedMail;
use App\Mail\PaymentRejectedMail;
use App\Models\Payment;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Liste en cartes (pas un <table>), choix délibéré : chaque paiement porte une preuve
 * (image), un verdict de contrôle automatique et plusieurs signaux — du contenu trop
 * riche pour des colonnes. Même raisonnement que Messages/RegistrationLeads/
 * CommunityModeration (audit architecture) : les écrans "fiche à consulter" restent en
 * cartes, les écrans "liste de champs comparables" restent en <table>.
 */
#[Layout('components.layouts.admin')]
class Payments extends Component
{
    use WithPagination;

    #[Url]
    public string $filter = 'a_verifier';

    /** Paiement dont le panneau de refus est ouvert. */
    public ?int $rejecting = null;

    public string $rejectReason = '';

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
        $this->rejecting = null;
        $this->resetPage();
    }

    public function confirm(int $paymentId): void
    {
        $payment = Payment::toVerify()->findOrFail($paymentId);

        if ($payment->confirm(auth()->user())) {
            $this->safeMail(fn () => Mail::to($payment->user->email)->send(new PaymentConfirmedMail($payment)));
            $this->dispatch('notify', message: "Paiement {$payment->reference} confirmé — accès débloqué.");
        }
    }

    public function startReject(int $paymentId): void
    {
        $this->rejecting = $this->rejecting === $paymentId ? null : $paymentId;
        $this->rejectReason = '';
    }

    public function reject(): void
    {
        $this->validate(['rejectReason' => ['required', 'string', 'min:5', 'max:300']]);

        $payment = Payment::toVerify()->findOrFail($this->rejecting);

        if ($payment->reject(auth()->user(), $this->rejectReason)) {
            $this->safeMail(fn () => Mail::to($payment->user->email)->send(new PaymentRejectedMail($payment)));
            $this->dispatch('notify', message: "Paiement {$payment->reference} refusé.");
        }

        $this->reset('rejecting', 'rejectReason');
    }

    private function safeMail(callable $send): void
    {
        try {
            $send();
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function render()
    {
        $query = Payment::with(['user', 'payable', 'enrollment.formation', 'order'])
            ->latest('submitted_at');

        if (in_array($this->filter, ['a_verifier', 'confirme', 'refuse'], true)) {
            $query->where('status', $this->filter);
        }

        return view('livewire.admin.payments', [
            'payments' => $query->paginate(15),
            'stats' => [
                'pending' => Payment::toVerify()->count(),
                'pendingSum' => (int) Payment::toVerify()->sum('amount'),
                'confirmed' => Payment::confirmed()->count(),
                'confirmedSum' => (int) Payment::confirmed()->sum('amount'),
                'refused' => Payment::refused()->count(),
            ],
        ]);
    }
}

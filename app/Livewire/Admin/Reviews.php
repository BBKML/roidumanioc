<?php

namespace App\Livewire\Admin;

use App\Enums\ReviewDirection;
use App\Models\Review;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Modération des évaluations (Phase 12, §25 — « Marketplace : évaluations », jusque-là
 * seulement un compteur en lecture seule sur Admin\ProducerShow). Ne permet JAMAIS
 * d'éditer/supprimer le contenu d'un avis (immuable, cf. Review) — seulement de le
 * MASQUER de l'affichage/des moyennes publiques en cas d'abus, motif obligatoire et
 * journalisé (même patron que Admin\Collaborations::resolveDispute — motif + LogsActivity).
 * Pas de policy dédiée : protégé comme le reste par le middleware `admin`, même choix que
 * ProducerProfile::verify()/rejectVerification() (aucun authorize() ici non plus).
 */
#[Layout('components.layouts.admin')]
class Reviews extends Component
{
    use WithPagination;

    #[Url]
    public string $filter = 'tous';

    public ?int $hidingId = null;

    public string $hideReason = '';

    public function updatingFilter(): void
    {
        $this->resetPage();
    }

    public function startHiding(int $reviewId): void
    {
        $this->hidingId = $reviewId;
        $this->hideReason = '';
    }

    public function cancelHiding(): void
    {
        $this->hidingId = null;
        $this->hideReason = '';
    }

    public function hide(): void
    {
        $review = Review::findOrFail($this->hidingId);

        $this->validate(['hideReason' => ['required', 'string', 'max:500']]);

        if ($review->hide(Auth::user(), $this->hideReason)) {
            $this->dispatch('notify', message: 'Avis masqué.');
            $this->cancelHiding();
        }
    }

    public function unhide(Review $review): void
    {
        if ($review->unhide()) {
            $this->dispatch('notify', message: 'Avis réaffiché.');
        }
    }

    public function render()
    {
        $reviews = Review::query()
            ->with(['collaboration.producerProfile', 'collaboration.buyerProfile', 'rater', 'ratee'])
            ->when($this->filter === 'visibles', fn ($q) => $q->whereNull('hidden_at'))
            ->when($this->filter === 'masques', fn ($q) => $q->whereNotNull('hidden_at'))
            ->latest()
            ->paginate(25);

        return view('livewire.admin.reviews', [
            'reviews' => $reviews,
            'directions' => ReviewDirection::cases(),
            'counts' => [
                'tous' => Review::count(),
                'masques' => Review::whereNotNull('hidden_at')->count(),
            ],
        ]);
    }
}

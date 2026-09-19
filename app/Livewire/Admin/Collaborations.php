<?php

namespace App\Livewire\Admin;

use App\Enums\CollaborationStatus;
use App\Models\Collaboration;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Vue d'ensemble des collaborations + gestion des litiges (Phase 10, §25/§37).
 *
 * Signaler un litige a déjà son propre écran (App\Livewire\Collaboration\Show, visible à
 * l'admin via `isViewerAdmin` — Phase 7) : pas de doublon ici, seulement un lien « Voir »
 * vers ce même écran partagé. Ce qui manquait et que cet écran ajoute : une vue
 * d'ensemble de TOUTES les collaborations, et — inédit avant cette phase — un moyen de
 * RÉSOUDRE un litige déjà signalé (`Collaboration::resolveDispute()`, symétrique de
 * `markDisputed()`, remet `en_cours` plutôt que `terminee` : ce n'est pas à l'admin de
 * décider que les parties se sont entendues).
 */
#[Layout('components.layouts.admin')]
class Collaborations extends Component
{
    use WithPagination;

    #[Url]
    public string $filter = 'litige';

    public ?int $resolvingId = null;

    public string $resolution = '';

    public function updatingFilter(): void
    {
        $this->resetPage();
    }

    public function startResolving(int $collaborationId): void
    {
        $this->resolvingId = $collaborationId;
        $this->resolution = '';
    }

    public function cancelResolving(): void
    {
        $this->resolvingId = null;
        $this->resolution = '';
    }

    public function resolveDispute(): void
    {
        $collaboration = Collaboration::findOrFail($this->resolvingId);

        // Même convention que Collaboration\Show (markDisputed, cancel, etc.) : chaque
        // transition passe par authorize(), pas seulement par le middleware `admin` de la
        // route — audit sécurité V1.
        $this->authorize('resolveDispute', $collaboration);

        $this->validate(['resolution' => ['required', 'string', 'max:500']]);

        if ($collaboration->resolveDispute(Auth::user(), $this->resolution)) {
            $this->dispatch('notify', message: 'Litige résolu — la collaboration repasse en cours.');
            $this->cancelResolving();
        }
    }

    public function render()
    {
        $collaborations = Collaboration::query()
            ->with(['producerProfile', 'buyerProfile'])
            ->when($this->filter !== 'tous', fn ($q) => $q->where('status', $this->filter))
            ->latest()
            ->paginate(20);

        return view('livewire.admin.collaborations', [
            'collaborations' => $collaborations,
            'statuses' => CollaborationStatus::cases(),
            'counts' => [
                'litige' => Collaboration::where('status', CollaborationStatus::Litige)->count(),
                'tous' => Collaboration::count(),
            ],
        ]);
    }
}

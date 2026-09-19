<?php

namespace App\Livewire\Admin;

use App\Enums\UserStatus;
use App\Models\BuyerProfile;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Supervision des acheteurs (Phase 10, §25/§37) — pas de vérification d'identité côté
 * acheteur (aucun badge public équivalent, cf. CLAUDE.md), donc la seule action propre à
 * cet écran est la suspension du compte — même mécanique exacte que
 * `Admin\Members::toggleSuspend()` (bascule `User.status`, pas de méthode dédiée sur le
 * modèle), avec les mêmes garde-fous anti-verrouillage même si un acheteur n'est jamais
 * admin en pratique — défense en profondeur, coût nul.
 */
#[Layout('components.layouts.admin')]
class Buyers extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $filter = 'tous';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilter(): void
    {
        $this->resetPage();
    }

    /** Recherche/filtre sans résultat (audit architecture) : un vrai bouton pour repartir de zéro. */
    public function resetFilters(): void
    {
        $this->reset('search', 'filter');
    }

    public function toggleSuspend(int $profileId): void
    {
        $profile = BuyerProfile::with('user')->findOrFail($profileId);
        $user = $profile->user;

        abort_if($user->id === auth()->id(), 403, 'Action impossible sur votre propre compte.');

        if ($user->isActive() && $user->isLastActiveAdmin()) {
            $this->dispatch('notify', message: 'Impossible : ce serait le dernier administrateur actif.');

            return;
        }

        // forceFill (pas update()) : 'status' n'est plus dans User::$fillable.
        $user->forceFill([
            'status' => $user->isActive() ? UserStatus::Suspendu : UserStatus::Actif,
        ])->save();
        $this->dispatch('notify', message: $user->isActive() ? 'Compte réactivé.' : 'Compte suspendu.');
    }

    public function render()
    {
        $buyers = BuyerProfile::query()
            ->with('user')
            ->when($this->search !== '', fn ($q) => $q->where(
                fn ($q2) => $q2->where('company_name', 'like', "%{$this->search}%")
                    ->orWhere('zone', 'like', "%{$this->search}%")
            ))
            ->when($this->filter === 'suspendus', fn ($q) => $q->whereHas('user', fn ($q2) => $q2->where('status', UserStatus::Suspendu)))
            ->withCount('buyerNeeds')
            ->latest()
            ->paginate(25);

        return view('livewire.admin.buyers', [
            'buyers' => $buyers,
            'stats' => ['total' => BuyerProfile::count()],
        ]);
    }
}

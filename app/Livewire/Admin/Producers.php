<?php

namespace App\Livewire\Admin;

use App\Models\ProducerProfile;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Supervision des producteurs (Phase 10, §25/§37) — remplace et étend
 * l'ancien Admin\ProducerVerification (Phase 8) : même vérification/révocation
 * (`ProducerProfile::verify()`/`rejectVerification()`, idempotentes), plus une recherche
 * et un lien vers la fiche détail (Admin\ProducerShow). Pas de policy dédiée : protégée
 * comme le reste du back-office par le middleware `admin` (même raisonnement que l'écran
 * qu'elle remplace — verify()/rejectVerification() n'ont pas besoin d'un garde-fou
 * d'acteur supplémentaire, la route s'en charge déjà).
 */
#[Layout('components.layouts.admin')]
class Producers extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $filter = 'a_verifier';

    /** Colonnes triables (audit architecture) — liste blanche : $sort vient de l'URL, jamais fiable tel quel. */
    private const SORTABLE = ['business_name', 'zone', 'crop_offers_count', 'created_at'];

    #[Url]
    public string $sort = 'created_at';

    #[Url]
    public string $direction = 'desc';

    public function sortBy(string $field): void
    {
        if (! in_array($field, self::SORTABLE, true)) {
            return;
        }

        $this->direction = $this->sort === $field && $this->direction === 'asc' ? 'desc' : 'asc';
        $this->sort = $field;
    }

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

    public function verify(ProducerProfile $profile): void
    {
        if ($profile->verify(Auth::user())) {
            $this->dispatch('notify', message: 'Producteur vérifié.');
        }
    }

    public function reject(ProducerProfile $profile): void
    {
        if ($profile->rejectVerification()) {
            $this->dispatch('notify', message: 'Vérification retirée.');
        }
    }

    public function render()
    {
        // $sort/$direction viennent de l'URL (#[Url]) : jamais utilisés tels quels dans la requête.
        $sort = in_array($this->sort, self::SORTABLE, true) ? $this->sort : 'created_at';
        $direction = $this->direction === 'asc' ? 'asc' : 'desc';

        $profiles = ProducerProfile::query()
            ->with('user')
            ->when($this->filter === 'a_verifier', fn ($q) => $q->whereNull('verified_at'))
            ->when($this->filter === 'verifies', fn ($q) => $q->whereNotNull('verified_at'))
            ->when($this->search !== '', fn ($q) => $q->where(
                fn ($q2) => $q2->where('business_name', 'like', "%{$this->search}%")
                    ->orWhere('zone', 'like', "%{$this->search}%")
            ))
            ->withCount('cropOffers')
            ->orderBy($sort, $direction)
            ->paginate(25);

        return view('livewire.admin.producers', [
            'profiles' => $profiles,
            'stats' => [
                'total' => ProducerProfile::count(),
                'verifies' => ProducerProfile::whereNotNull('verified_at')->count(),
            ],
        ]);
    }
}

<?php

namespace App\Livewire\Admin;

use App\Enums\EnrollmentStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Mail\MemberPasswordResetMail;
use App\Mail\MemberWelcomeMail;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class Members extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $filter = 'tous';

    /** Colonnes triables (audit architecture) — liste blanche : $sort vient de l'URL, jamais fiable tel quel. */
    private const SORTABLE = ['name', 'city', 'validated_count', 'created_at'];

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

    /** Recherche/filtre sans résultat (audit UX, Phase 2) : un vrai bouton pour repartir de zéro. */
    public function resetFilters(): void
    {
        $this->reset('search', 'filter');
    }

    public bool $showForm = false;

    public string $name = '';

    public ?string $email = null;

    public string $role = 'apprenant';

    public ?string $city = null;

    public ?string $phone = null;

    /** Mot de passe temporaire à montrer une seule fois après création / réinitialisation. */
    public ?string $tempPassword = null;

    public ?string $tempPasswordFor = null;

    /* ---------------- Création ---------------- */

    public function newMember(): void
    {
        $this->reset('name', 'email', 'city', 'phone', 'tempPassword', 'tempPasswordFor');
        $this->role = 'apprenant';
        $this->resetValidation();
        $this->showForm = true;
    }

    public function createMember(): void
    {
        $this->email = blank($this->email) ? null : $this->email;
        $this->phone = PhoneNumber::normalize($this->phone);

        $data = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'required_without:phone', 'email', 'max:180', Rule::unique('users', 'email')],
            'role' => ['required', Rule::enum(UserRole::class)],
            'city' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'required_without:email', 'string', 'max:20', Rule::unique('users', 'phone')],
        ]);

        $password = Str::password(14);

        $user = new User;
        $user->forceFill([
            ...$data,
            'password' => Hash::make($password),
            'password_changed_at' => null,        // le membre devra le changer
            'status' => UserStatus::Actif->value,
            'email_verified_at' => now(),         // v1 : pas de vérification e-mail
            'joined_at' => now(),
        ])->save();

        $this->showForm = false;
        $this->tempPassword = $password;
        $this->tempPasswordFor = $user->name;

        $notified = false;
        if ($user->email) {
            try {
                Mail::to($user->email)->send(new MemberWelcomeMail($user, $password));
                $notified = true;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $this->dispatch('notify', message: $notified ? 'Compte créé — identifiants envoyés par e-mail.' : 'Compte créé.');
    }

    /* ---------------- Rôle ---------------- */

    public function changeRole(int $userId): void
    {
        $user = User::findOrFail($userId);

        abort_if($user->id === auth()->id(), 403, 'Vous ne pouvez pas modifier votre propre rôle.');

        $next = $user->isAdmin() ? UserRole::Apprenant : UserRole::Admin;

        if ($next === UserRole::Apprenant && $user->isLastActiveAdmin()) {
            $this->dispatch('notify', message: 'Impossible : ce serait le dernier administrateur.');

            return;
        }

        $user->update(['role' => $next]);
        $this->dispatch('notify', message: "{$user->name} est maintenant ".$next->label().'.');
    }

    /* ---------------- Suspension ---------------- */

    public function toggleSuspend(int $userId): void
    {
        $user = User::findOrFail($userId);

        abort_if($user->id === auth()->id(), 403, 'Vous ne pouvez pas suspendre votre propre compte.');

        if ($user->isActive() && $user->isLastActiveAdmin()) {
            $this->dispatch('notify', message: 'Impossible : ce serait le dernier administrateur actif.');

            return;
        }

        $user->update([
            'status' => $user->isActive() ? UserStatus::Suspendu : UserStatus::Actif,
        ]);
        $this->dispatch('notify', message: $user->isActive() ? 'Compte réactivé.' : 'Compte suspendu.');
    }

    /* ---------------- Réinitialisation du mot de passe ---------------- */

    public function resetPassword(int $userId): void
    {
        $user = User::findOrFail($userId);

        abort_if($user->id === auth()->id(), 403, 'Utilisez « Mon compte » pour votre propre mot de passe.');

        $password = Str::password(14);
        $user->forceFill([
            'password' => Hash::make($password),
            'password_changed_at' => null,
        ])->save();

        activity('user')->performedOn($user)->causedBy(auth()->user())->log('password_reset_by_admin');

        $this->tempPassword = $password;
        $this->tempPasswordFor = $user->name;

        $notified = false;
        if ($user->email) {
            try {
                Mail::to($user->email)->send(new MemberPasswordResetMail($user, $password));
                $notified = true;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $this->dispatch('notify', message: $notified ? 'Mot de passe réinitialisé — envoyé par e-mail.' : 'Mot de passe réinitialisé.');
    }

    /* ---------------- Suppression ---------------- */

    public function clearDeliveryStrikes(int $userId): void
    {
        $user = User::findOrFail($userId);
        $user->update(['delivery_strikes' => 0]);
        activity('user')->performedOn($user)->causedBy(auth()->user())->log('delivery_strikes_cleared');
        $this->dispatch('notify', message: 'Paiement à la livraison réactivé pour '.$user->name.'.');
    }

    public function deleteMember(int $userId): void
    {
        $user = User::findOrFail($userId);

        abort_if($user->id === auth()->id(), 403, 'Vous ne pouvez pas supprimer votre propre compte ici.');

        if ($user->isLastActiveAdmin()) {
            $this->dispatch('notify', message: 'Impossible de supprimer le dernier administrateur.');

            return;
        }

        if ($user->payments()->exists() || $user->orders()->exists()) {
            $this->dispatch('notify', message: 'Ce compte a un historique de paiements — suspendez-le plutôt que de le supprimer.');

            return;
        }

        $user->delete();
        $this->dispatch('notify', message: 'Compte supprimé.');
    }

    /* ---------------- Rendu ---------------- */

    public function render()
    {
        // $sort/$direction viennent de l'URL (#[Url]) : jamais utilisés tels quels dans la requête.
        $sort = in_array($this->sort, self::SORTABLE, true) ? $this->sort : 'created_at';
        $direction = $this->direction === 'asc' ? 'asc' : 'desc';

        $query = User::query()
            ->withCount(['enrollments as validated_count' => fn ($q) => $q->where('status', EnrollmentStatus::Validee)])
            ->orderBy($sort, $direction);

        if ($this->search !== '') {
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")
                ->orWhere('phone', 'like', "%{$this->search}%")
                ->orWhere('city', 'like', "%{$this->search}%"));
        }

        match ($this->filter) {
            'admin' => $query->where('role', UserRole::Admin),
            'apprenant' => $query->where('role', UserRole::Apprenant),
            'suspendu' => $query->where('status', UserStatus::Suspendu),
            default => null,
        };

        return view('livewire.admin.members', [
            'members' => $query->paginate(25),
            'stats' => [
                'total' => User::count(),
                'active' => User::where('status', UserStatus::Actif)->count(),
                'admins' => User::where('role', UserRole::Admin)->count(),
            ],
        ]);
    }
}

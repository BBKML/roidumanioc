<?php

namespace App\Livewire\Account;

use App\Enums\EnrollmentStatus;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

class Settings extends Component
{
    // Identité
    public string $name = '';

    public ?string $email = null;

    public ?string $phone = null;

    public ?string $city = null;

    // Mot de passe
    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    // Suppression de compte
    public string $delete_password = '';

    public function mount(): void
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone;
        $this->city = $user->city;
    }

    public function updateProfile(): void
    {
        $user = Auth::user();
        $this->email = blank($this->email) ? null : $this->email;
        $this->phone = PhoneNumber::normalize($this->phone);

        $data = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'required_without:phone', 'email', 'max:180', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'required_without:email', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($user->id)],
            'city' => ['nullable', 'string', 'max:120'],
        ]);

        // e-mail modifié -> re-vérification (v1 : on marque simplement non vérifié)
        if (($data['email'] ?? null) !== $user->email) {
            $user->email_verified_at = $user->provider === 'google' ? now() : null;
        }

        $user->fill($data)->save();

        $this->dispatch('notify', message: 'Informations enregistrées.');
    }

    public function updatePassword(): void
    {
        $user = Auth::user();

        $rules = [
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
        // Un compte « Google uniquement » n'a pas de mot de passe courant à saisir.
        if (! $user->isOAuthOnly()) {
            $rules['current_password'] = ['required', 'current_password'];
        }

        $this->validate($rules);

        $user->forceFill([
            'password' => Hash::make($this->password),
            'password_changed_at' => now(),
        ])->save();

        // Invalide les autres sessions du même compte (best effort).
        try {
            Auth::logoutOtherDevices($this->password);
        } catch (\Throwable $e) {
            report($e);
        }

        $this->reset('current_password', 'password', 'password_confirmation');
        $this->dispatch('notify', message: 'Mot de passe mis à jour.');
    }

    public function deleteAccount()
    {
        $user = Auth::user();

        abort_if($user->isLastActiveAdmin(), 403, 'Impossible de supprimer le dernier administrateur.');

        $rules = $user->isOAuthOnly()
            ? []
            : ['delete_password' => ['required', 'current_password']];
        $this->validate($rules);

        Auth::logout();
        $user->delete();

        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('home');
    }

    public function render()
    {
        $user = Auth::user();
        $layout = $user->isAdmin() ? 'components.layouts.admin' : 'components.layouts.learner';

        $summary = $user->isAdmin() ? null : [
            'formations' => $user->enrollments()->where('status', EnrollmentStatus::Validee)->count(),
            'orders' => $user->orders()->count(),
            'payments' => $user->payments()->count(),
        ];

        $producerProfile = $user->producerProfile;
        $buyerProfile = $user->buyerProfile;

        return view('livewire.account.settings', compact('user', 'summary', 'producerProfile', 'buyerProfile'))
            ->layout($layout);
    }
}

<?php

namespace App\Livewire\Learner;

use App\Enums\BuyerType;
use App\Models\BuyerProfile as BuyerProfileModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class BuyerProfile extends Component
{
    public ?string $company_name = null;

    public ?string $buyer_type = null;

    public string $zone = '';

    public ?string $bio = null;

    public bool $exists = false;

    public bool $acceptedTerms = false;

    public function mount(): void
    {
        $profile = Auth::user()->buyerProfile;

        if ($profile) {
            $this->exists = true;
            $this->company_name = $profile->company_name;
            $this->buyer_type = $profile->buyer_type?->value;
            $this->zone = $profile->zone;
            $this->bio = $profile->bio;
        }
    }

    public function save(): void
    {
        $user = Auth::user();
        $profile = $user->buyerProfile ?? new BuyerProfileModel(['user_id' => $user->id]);

        $this->authorize('update', $profile);

        // Même famille que CreateConnectionRequest/DeclarePayment (§ Sécurité, audit V1) —
        // create ET édition passent par la même méthode, donc un seul garde-fou couvre les deux.
        $key = 'buyer-profile-save:'.$user->id;
        if (RateLimiter::tooManyAttempts($key, 10)) {
            throw ValidationException::withMessages([
                'company_name' => 'Trop de modifications. Réessayez plus tard.',
            ]);
        }
        RateLimiter::hit($key, 3600);

        $wasNew = ! $profile->exists;

        $data = $this->validate([
            'company_name' => ['nullable', 'string', 'max:150'],
            'buyer_type' => ['required', Rule::enum(BuyerType::class)],
            'zone' => ['required', 'string', 'max:120'],
            'bio' => ['nullable', 'string', 'max:2000'],
            // Uniquement à la première activation (§44) — déjà accepté ensuite.
            'acceptedTerms' => $wasNew ? ['accepted'] : [],
        ], [
            'acceptedTerms.accepted' => "Merci d'accepter les conditions générales d'utilisation et la politique de confidentialité.",
        ]);

        unset($data['acceptedTerms']);
        if ($wasNew) {
            $data['terms_accepted_at'] = now();
        }

        $profile->forceFill($data)->save();

        $this->exists = true;

        $this->dispatch('notify', message: $wasNew ? 'Profil acheteur activé.' : 'Profil acheteur mis à jour.');
    }

    public function render()
    {
        return view('livewire.learner.buyer-profile')->layout('components.layouts.learner');
    }
}

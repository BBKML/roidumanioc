<?php

namespace App\Livewire\Learner;

use App\Enums\ActivityType;
use App\Models\ProducerProfile as ProducerProfileModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

class ProducerProfile extends Component
{
    use WithFileUploads;

    public string $business_name = '';

    public ?string $bio = null;

    public string $zone = '';

    public ?string $activity_type = null;

    public ?string $capacity_note = null;

    public ?int $years_active = null;

    public ?string $logo_path = null;

    public $logo = null;

    public bool $exists = false;

    public bool $acceptedTerms = false;

    public function mount(): void
    {
        $profile = Auth::user()->producerProfile;

        if ($profile) {
            $this->exists = true;
            $this->business_name = $profile->business_name;
            $this->bio = $profile->bio;
            $this->zone = $profile->zone;
            $this->activity_type = $profile->activity_type?->value;
            $this->capacity_note = $profile->capacity_note;
            $this->years_active = $profile->years_active;
            $this->logo_path = $profile->logo_path;
        }
    }

    public function save(): void
    {
        $user = Auth::user();
        $profile = $user->producerProfile ?? new ProducerProfileModel(['user_id' => $user->id]);

        $this->authorize('update', $profile);

        // Même famille que CreateConnectionRequest/DeclarePayment (§ Sécurité, audit V1) —
        // create ET édition passent par la même méthode, donc un seul garde-fou couvre les deux.
        $key = 'producer-profile-save:'.$user->id;
        if (RateLimiter::tooManyAttempts($key, 10)) {
            throw ValidationException::withMessages([
                'business_name' => 'Trop de modifications. Réessayez plus tard.',
            ]);
        }
        RateLimiter::hit($key, 3600);

        $wasNew = ! $profile->exists;

        $data = $this->validate([
            'business_name' => ['required', 'string', 'max:150'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'zone' => ['required', 'string', 'max:120'],
            'activity_type' => ['required', Rule::enum(ActivityType::class)],
            'capacity_note' => ['nullable', 'string', 'max:255'],
            'years_active' => ['nullable', 'integer', 'min:0', 'max:80'],
            'logo' => ['nullable', 'image', 'max:4096'],
            // Uniquement à la première activation (§44) — déjà accepté ensuite, on ne
            // redemande pas la case à chaque modification du profil.
            'acceptedTerms' => $wasNew ? ['accepted'] : [],
        ], [
            'acceptedTerms.accepted' => "Merci d'accepter les conditions générales d'utilisation et la politique de confidentialité.",
        ]);

        if ($this->logo) {
            if ($profile->logo_path) {
                Storage::disk('public')->delete($profile->logo_path);
            }
            $data['logo_path'] = $this->logo->store('producer-profiles', 'public');
            $this->logo = null;
        }
        unset($data['logo'], $data['acceptedTerms']);

        if ($wasNew) {
            $data['terms_accepted_at'] = now();
        }

        $profile->forceFill($data)->save();

        $this->exists = true;
        $this->logo_path = $profile->logo_path;

        $this->dispatch('notify', message: $wasNew ? 'Profil producteur activé.' : 'Profil producteur mis à jour.');
    }

    public function render()
    {
        return view('livewire.learner.producer-profile')->layout('components.layouts.learner');
    }
}

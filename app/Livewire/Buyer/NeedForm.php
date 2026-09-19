<?php

namespace App\Livewire\Buyer;

use App\Enums\BuyerNeedStatus;
use App\Enums\CropUnit;
use App\Models\BuyerNeed;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.learner')]
class NeedForm extends Component
{
    public ?BuyerNeed $need = null;

    public string $product_wanted = '';

    public ?string $quantity = null;

    public string $unit = 'kg';

    public string $location = '';

    public ?string $wanted_date = null;

    public string $frequency = 'ponctuel';

    public ?string $quality_desc = null;

    public ?int $budget_indicative = null;

    public ?string $description = null;

    public string $status = 'ouvert';

    public function mount(?BuyerNeed $need = null): void
    {
        if ($need) {
            $this->authorize('update', $need);

            $this->need = $need;
            $this->product_wanted = $need->product_wanted;
            $this->quantity = (string) $need->quantity;
            $this->unit = $need->unit->value;
            $this->location = $need->location;
            $this->wanted_date = $need->wanted_date?->format('Y-m-d');
            $this->frequency = $need->frequency;
            $this->quality_desc = $need->quality_desc;
            $this->budget_indicative = $need->budget_indicative;
            $this->description = $need->description;
            $this->status = $need->status->value;
        } else {
            $this->location = Auth::user()->buyerProfile->zone;
        }
    }

    protected function rules(): array
    {
        return [
            'product_wanted' => ['required', 'string', 'max:150'],
            'quantity' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'unit' => ['required', Rule::enum(CropUnit::class)],
            'location' => ['required', 'string', 'max:150'],
            'wanted_date' => ['nullable', 'date'],
            'frequency' => ['required', Rule::in(['ponctuel', 'recurrent'])],
            'quality_desc' => ['nullable', 'string', 'max:255'],
            'budget_indicative' => ['nullable', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::enum(BuyerNeedStatus::class)],
        ];
    }

    public function save()
    {
        $buyerProfile = Auth::user()->buyerProfile;
        abort_unless($buyerProfile, 403);

        if ($this->need) {
            $this->authorize('update', $this->need);
        }

        // La route `.create` a déjà `throttle:6,1` (re-appliqué à chaque wire:click via
        // Livewire::addPersistentMiddleware) mais `.edit` n'a aucune limite — ce garde-fou
        // ici couvre les deux uniformément, au même rythme (audit sécurité V1).
        $key = 'need-save:'.Auth::id();
        if (RateLimiter::tooManyAttempts($key, 6)) {
            throw ValidationException::withMessages([
                'product_wanted' => 'Trop de modifications. Réessayez plus tard.',
            ]);
        }
        RateLimiter::hit($key, 60);

        $data = $this->validate();

        $isNew = ! $this->need;
        $need = $this->need ?? new BuyerNeed(['buyer_profile_id' => $buyerProfile->id]);
        $need->fill($data)->save();

        if ($isNew) {
            $this->dispatch('notify', message: 'Besoin publié.');

            return redirect()->route('learner.buyer.needs.edit', $need);
        }

        $this->need = $need;
        $this->dispatch('notify', message: 'Besoin enregistré.');

        return null;
    }

    public function render()
    {
        return view('livewire.buyer.need-form', [
            'units' => CropUnit::cases(),
            'statuses' => BuyerNeedStatus::cases(),
        ]);
    }
}

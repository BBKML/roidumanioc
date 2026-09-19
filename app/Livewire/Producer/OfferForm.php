<?php

namespace App\Livewire\Producer;

use App\Enums\CropOfferStatus;
use App\Enums\CropUnit;
use App\Models\CropOffer;
use App\Models\CropOfferPhoto;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('components.layouts.learner')]
class OfferForm extends Component
{
    use WithFileUploads;

    const MAX_PHOTOS = 5;

    public ?CropOffer $offer = null;

    public string $product_name = '';

    public ?string $variety = null;

    public ?string $quantity = null;

    public string $unit = 'kg';

    public ?int $price_indicative = null;

    public string $location = '';

    public bool $is_available = true;

    public ?string $available_from = null;

    public ?string $description = null;

    public string $status = 'brouillon';

    /** @var array<int, TemporaryUploadedFile> */
    public array $newPhotos = [];

    public function mount(?CropOffer $offer = null): void
    {
        if ($offer) {
            $this->authorize('update', $offer);

            $this->offer = $offer;
            $this->product_name = $offer->product_name;
            $this->variety = $offer->variety;
            $this->quantity = (string) $offer->quantity;
            $this->unit = $offer->unit->value;
            $this->price_indicative = $offer->price_indicative;
            $this->location = $offer->location;
            $this->is_available = $offer->is_available;
            $this->available_from = $offer->available_from?->format('Y-m-d');
            $this->description = $offer->description;
            $this->status = $offer->status->value;
        } else {
            $this->location = Auth::user()->producerProfile->zone;
        }
    }

    protected function existingPhotosCount(): int
    {
        return $this->offer?->photos()->count() ?? 0;
    }

    protected function rules(): array
    {
        return [
            'product_name' => ['required', 'string', 'max:150'],
            'variety' => ['nullable', 'string', 'max:100'],
            'quantity' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'unit' => ['required', Rule::enum(CropUnit::class)],
            'price_indicative' => ['nullable', 'integer', 'min:1'],
            'location' => ['required', 'string', 'max:150'],
            'is_available' => ['boolean'],
            'available_from' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::enum(CropOfferStatus::class)],
            'newPhotos' => ['array', function ($attribute, $value, $fail) {
                if ($this->existingPhotosCount() + count($value) > self::MAX_PHOTOS) {
                    $fail('Maximum '.self::MAX_PHOTOS.' photos par offre.');
                }
            }],
            'newPhotos.*' => ['image', 'max:4096'],
        ];
    }

    public function removeExistingPhoto(CropOfferPhoto $photo): void
    {
        abort_unless($this->offer && $photo->crop_offer_id === $this->offer->id, 404);
        $this->authorize('update', $this->offer);

        Storage::disk('public')->delete($photo->path);
        $photo->delete();
    }

    public function save()
    {
        $producerProfile = Auth::user()->producerProfile;
        abort_unless($producerProfile, 403);

        if ($this->offer) {
            $this->authorize('update', $this->offer);
        }

        // La route `.create` a déjà `throttle:6,1` (re-appliqué à chaque wire:click via
        // Livewire::addPersistentMiddleware) mais `.edit` n'a aucune limite — ce garde-fou
        // ici couvre les deux uniformément, au même rythme (audit sécurité V1).
        $key = 'offer-save:'.Auth::id();
        if (RateLimiter::tooManyAttempts($key, 6)) {
            throw ValidationException::withMessages([
                'product_name' => 'Trop de modifications. Réessayez plus tard.',
            ]);
        }
        RateLimiter::hit($key, 60);

        $data = $this->validate();
        unset($data['newPhotos']);

        $isNew = ! $this->offer;
        $offer = $this->offer ?? new CropOffer(['producer_profile_id' => $producerProfile->id]);
        $offer->fill($data)->save();

        $nextPosition = (int) $offer->photos()->max('position');
        foreach ($this->newPhotos as $photo) {
            $offer->photos()->create([
                'path' => $photo->store('crop-offers', 'public'),
                'position' => ++$nextPosition,
            ]);
        }
        $this->newPhotos = [];

        if ($isNew) {
            $this->dispatch('notify', message: 'Offre créée.');

            return redirect()->route('learner.producer.offers.edit', $offer);
        }

        $this->offer = $offer;
        $this->dispatch('notify', message: 'Offre enregistrée.');

        return null;
    }

    public function render()
    {
        return view('livewire.producer.offer-form', [
            'units' => CropUnit::cases(),
            'statuses' => CropOfferStatus::cases(),
            'existingPhotos' => $this->offer?->photos ?? collect(),
        ]);
    }
}

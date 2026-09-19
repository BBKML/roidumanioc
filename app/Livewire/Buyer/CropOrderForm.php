<?php

namespace App\Livewire\Buyer;

use App\Actions\CreateCropOrder;
use App\Models\CropOffer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * « Passer une commande » (§1/§2) — formulaire structuré, distinct du contact libre par
 * chat (App\Livewire\Connect\RequestOffer). La commande créée démarre TOUJOURS au statut
 * « en attente du producteur », jamais acceptée automatiquement.
 */
#[Layout('components.layouts.learner')]
class CropOrderForm extends Component
{
    /** Liste fermée + « Autre » (texte libre), même patron que RegistrationFormController::STATUS_FUNCTIONS. */
    public const PAYMENT_METHODS = [
        'Mobile Money (Wave / Orange Money / MTN / Moov)',
        'Espèces à la livraison',
        'Virement bancaire',
        'Autre',
    ];

    public CropOffer $offer;

    public ?float $quantity = null;

    public string $unit = '';

    public string $deliveryLocation = '';

    public ?string $desiredDate = null;

    public string $deliveryNotes = '';

    public string $paymentMethod = '';

    public string $paymentMethodOther = '';

    public string $qualityExpected = '';

    public string $buyerMessage = '';

    public function mount(CropOffer $offer): void
    {
        abort_unless(CropOffer::published()->whereKey($offer->id)->exists(), 404);
        abort_if($offer->producerProfile->user_id === Auth::id(), 403, 'Vous ne pouvez pas commander votre propre offre.');

        $this->offer = $offer;
        $this->quantity = (float) $offer->quantity;
        $this->unit = $offer->unit->value;
        $this->deliveryLocation = $offer->location;
    }

    public function send()
    {
        $validated = $this->validate([
            'quantity' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'unit' => ['required', 'in:'.implode(',', array_map(fn ($u) => $u->value, \App\Enums\CropUnit::cases()))],
            'deliveryLocation' => ['required', 'string', 'max:255'],
            'desiredDate' => ['nullable', 'date', 'after_or_equal:today'],
            'deliveryNotes' => ['nullable', 'string', 'max:1000'],
            'paymentMethod' => ['required', 'in:'.implode(',', self::PAYMENT_METHODS)],
            'paymentMethodOther' => ['nullable', 'string', 'max:100', 'required_if:paymentMethod,Autre'],
            'qualityExpected' => ['nullable', 'string', 'max:255'],
            'buyerMessage' => ['nullable', 'string', 'max:1000'],
        ]);

        $paymentMethod = $validated['paymentMethod'];
        if ($paymentMethod === 'Autre') {
            $paymentMethod = $validated['paymentMethodOther'] ?: 'Autre';
        }

        try {
            $cropOrder = app(CreateCropOrder::class)->handle(
                Auth::user(),
                $this->offer,
                (float) $validated['quantity'],
                $validated['unit'],
                $validated['deliveryLocation'],
                $validated['desiredDate'] ? new \DateTimeImmutable($validated['desiredDate']) : null,
                $validated['deliveryNotes'] ?: null,
                $paymentMethod,
                $validated['qualityExpected'] ?: null,
                $validated['buyerMessage'] ?: null,
            );
        } catch (ValidationException $e) {
            $this->addError('general', $e->validator->errors()->first());

            return null;
        }

        $this->dispatch('notify', message: 'Commande envoyée au producteur.');

        return redirect()->route('learner.crop-orders.show', $cropOrder);
    }

    public function render()
    {
        return view('livewire.buyer.crop-order-form', ['units' => \App\Enums\CropUnit::cases()]);
    }
}

<?php

namespace App\Livewire\Admin;

use App\Models\PaymentSetting;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class PaymentSettings extends Component
{
    #[Validate('nullable|string|max:30')]
    public ?string $whatsapp = null;

    #[Validate('nullable|string|max:60')]
    public ?string $wave = null;

    #[Validate('nullable|string|max:60')]
    public ?string $orange = null;

    #[Validate('nullable|string|max:60')]
    public ?string $mtn = null;

    #[Validate('nullable|string|max:60')]
    public ?string $moov = null;

    #[Validate('nullable|string|max:120')]
    public ?string $bank_name = null;

    #[Validate('nullable|string|max:120')]
    public ?string $rib = null;

    #[Validate('nullable|url|max:255')]
    public ?string $intl_link = null;

    #[Validate('nullable|integer|min:0|max:1000000')]
    public ?int $delivery_fee = null;

    #[Validate('nullable|string|max:255')]
    public ?string $delivery_note = null;

    public function mount(): void
    {
        $this->fill(PaymentSetting::current()->only(
            'whatsapp', 'wave', 'orange', 'mtn', 'moov', 'bank_name', 'rib', 'intl_link',
            'delivery_fee', 'delivery_note',
        ));
    }

    public function save(): void
    {
        $data = $this->validate();

        PaymentSetting::current()->update($data);

        $this->dispatch('notify', message: 'Comptes de paiement enregistrés.');
    }

    public function render()
    {
        return view('livewire.admin.payment-settings');
    }
}

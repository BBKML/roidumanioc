<?php

namespace App\Livewire\Learner;

use App\Actions\DeclarePayment;
use App\Enums\PaymentMethod;
use App\Models\Formation;
use App\Models\PaymentSetting;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.learner')]
class Checkout extends Component
{
    use AuthorizesRequests, WithFileUploads;

    public Formation $formation;

    public string $method = 'wave';

    public ?int $declaredAmount = null;

    public ?string $transactionId = null;

    public $proof;

    public function mount(Formation $formation): void
    {
        $this->authorize('enroll', $formation);
        abort_if($formation->isFree(), 404);

        $this->formation = $formation;
        $this->declaredAmount = $formation->price;
    }

    protected function rules(): array
    {
        return [
            'method' => ['required', 'in:'.collect(PaymentMethod::cases())->map->value->implode(',')],
            'declaredAmount' => ['required', 'integer', 'min:1'],
            'transactionId' => ['nullable', 'string', 'max:60'],
            'proof' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    public function declarePayment(DeclarePayment $action)
    {
        $this->validate();

        $action->handle(
            user: auth()->user(),
            payable: $this->formation,
            method: $this->method,
            declaredAmount: (int) $this->declaredAmount,
            transactionId: $this->transactionId,
            proof: $this->proof,
        );

        session()->flash('flash', "Paiement déclaré (réf. suivie par e-mail). Votre accès s'ouvre dès vérification.");

        return $this->redirectRoute('learner.dashboard', navigate: true);
    }

    public function render()
    {
        $settings = PaymentSetting::current();

        return view('livewire.learner.checkout', [
            'settings' => $settings,
            'methods' => PaymentMethod::cases(),
            'whatsapp' => Str::of($settings->whatsapp ?? '')->replaceMatches('/\D+/', ''),
        ]);
    }
}

<?php

namespace App\Livewire\Learner;

use App\Actions\DeclarePayment;
use App\Enums\OrderPaymentMode;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Mail\NewOrderMail;
use App\Mail\OrderUpdateMail;
use App\Models\Order;
use App\Models\PaymentSetting;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.learner')]
class ShopCheckout extends Component
{
    use WithFileUploads;

    public ShopProduct $product;

    public int $quantity = 1;

    /** online | on_delivery */
    public string $mode = 'online';

    /** Le client a-t-il le droit de payer à la livraison ? */
    public bool $codAllowed = true;

    public ?string $codBlockedReason = null;

    // Livraison (toujours demandée — produit physique)
    public string $contactPhone = '';

    public string $deliveryAddress = '';

    public string $deliveryCity = '';

    public ?string $customerNote = null;

    // Paiement en ligne
    public string $method = 'wave';

    public ?int $declaredAmount = null;

    public ?string $transactionId = null;

    public $proof;

    public function mount(ShopProduct $product): void
    {
        abort_unless($product->is_active, 404);

        $user = auth()->user();
        $this->product = $product;
        $this->quantity = max(1, min((int) request('qty', 1), max(1, $product->stock)));
        $this->contactPhone = (string) $user->phone;
        $this->deliveryCity = (string) $user->city;
        $this->declaredAmount = $this->total();

        if (! $user->canPayOnDelivery()) {
            $this->codAllowed = false;
            $this->codBlockedReason = 'Suite à des livraisons non abouties, le paiement à la livraison n\'est plus disponible pour votre compte. Vous pouvez payer en ligne.';
        } elseif ($user->openDeliveryOrdersCount() >= 3) {
            $this->codAllowed = false;
            $this->codBlockedReason = 'Vous avez déjà 3 commandes « à la livraison » en cours. Réglez-les avant d\'en ouvrir une nouvelle, ou payez en ligne.';
        }

        if (! $this->codAllowed) {
            $this->mode = 'online';
        }
    }

    public function setQty(int $qty): void
    {
        $this->quantity = max(1, min($qty, max(1, $this->product->stock)));
        $this->declaredAmount = $this->total();
    }

    public function updatedMode(): void
    {
        $this->declaredAmount = $this->total();
        $this->resetValidation();
    }

    public function deliveryFee(): int
    {
        return (int) (PaymentSetting::current()->delivery_fee ?? 0);
    }

    public function subtotal(): int
    {
        return (int) $this->product->price * max(1, $this->quantity);
    }

    public function total(): int
    {
        return $this->subtotal() + $this->deliveryFee();
    }

    protected function rules(): array
    {
        $rules = [
            'quantity' => ['required', 'integer', 'min:1', 'max:'.max(1, $this->product->stock)],
            'mode' => ['required', 'in:online,on_delivery'],
            'contactPhone' => ['required', 'string', 'max:40'],
            'deliveryAddress' => ['required', 'string', 'max:255'],
            'deliveryCity' => ['required', 'string', 'max:120'],
            'customerNote' => ['nullable', 'string', 'max:500'],
        ];

        if ($this->mode === 'online') {
            $rules['method'] = ['required', 'in:'.collect(PaymentMethod::cases())->map->value->implode(',')];
            $rules['declaredAmount'] = ['required', 'integer', 'min:1'];
            $rules['transactionId'] = ['nullable', 'string', 'max:60'];
            $rules['proof'] = ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'];
        }

        return $rules;
    }

    public function placeOrder(DeclarePayment $payments)
    {
        $this->validate();

        abort_unless(auth()->user()->isActive(), 403);
        abort_if($this->mode === 'on_delivery' && ! $this->codAllowed, 403, 'Paiement à la livraison indisponible.');

        if ($this->product->stock < $this->quantity) {
            $this->addError('quantity', 'Stock insuffisant : il reste '.$this->product->stock.' unité(s).');

            return;
        }

        $order = auth()->user()->orders()->create([
            'customer_name' => auth()->user()->name,
            'contact_phone' => $this->contactPhone,
            'delivery_address' => $this->deliveryAddress,
            'delivery_city' => $this->deliveryCity,
            'orderable_type' => $this->product->getMorphClass(),
            'orderable_id' => $this->product->id,
            'item_label' => $this->product->name.($this->quantity > 1 ? " ×{$this->quantity}" : ''),
            'quantity' => $this->quantity,
            'amount' => $this->subtotal(),
            'delivery_fee' => $this->deliveryFee(),
            'customer_note' => $this->customerNote,
            'payment_mode' => $this->mode === 'online' ? OrderPaymentMode::Online : OrderPaymentMode::OnDelivery,
            'status' => $this->mode === 'online' ? OrderStatus::Paiement : OrderStatus::Validee,
        ]);

        if ($this->mode === 'online') {
            $payments->handle(
                user: auth()->user(),
                payable: $this->product,
                method: $this->method,
                declaredAmount: (int) $this->declaredAmount,
                transactionId: $this->transactionId,
                proof: $this->proof,
                quantity: $this->quantity,
                order: $order,
            );
            $flash = "Commande {$order->reference} enregistrée. Accès dès vérification du paiement.";
        } else {
            $this->notifyDeliveryOrder($order);
            $flash = "Commande {$order->reference} enregistrée. Vous réglez à la livraison.";
        }

        session()->flash('flash', $flash);

        return $this->redirectRoute('learner.orders', navigate: true);
    }

    private function notifyDeliveryOrder(Order $order): void
    {
        try {
            Mail::to($order->user->email)->send(new OrderUpdateMail($order, 'placed'));

            $adminEmail = User::where('role', 'admin')->where('status', 'actif')->value('email')
                ?: config('mail.from.address');
            Mail::to($adminEmail)->send(new NewOrderMail($order));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function render()
    {
        $settings = PaymentSetting::current();

        return view('livewire.learner.shop-checkout', [
            'settings' => $settings,
            'methods' => PaymentMethod::cases(),
            'whatsapp' => Str::of($settings->whatsapp ?? '')->replaceMatches('/\D+/', ''),
        ]);
    }
}

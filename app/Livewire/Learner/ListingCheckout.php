<?php

namespace App\Livewire\Learner;

use App\Enums\OrderPaymentMode;
use App\Enums\OrderStatus;
use App\Mail\ListingOrderMail;
use App\Mail\NewOrderMail;
use App\Mail\OrderUpdateMail;
use App\Models\MarketplaceListing;
use App\Models\Order;
use App\Models\PaymentSetting;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Commander une ANNONCE de producteur.
 * Le Roi du Manioc met en relation et historise — il ne touche jamais l'argent.
 * Le règlement se fait directement entre l'acheteur et le producteur.
 */
#[Layout('components.layouts.learner')]
class ListingCheckout extends Component
{
    public MarketplaceListing $listing;

    public string $quantityText = '';

    public string $contactPhone = '';

    public string $deliveryAddress = '';

    public string $deliveryCity = '';

    public ?string $message = null;

    public function mount(MarketplaceListing $listing): void
    {
        abort_unless($listing->status->value === 'validee', 404);

        $this->listing = $listing;
        $this->contactPhone = (string) auth()->user()->phone;
        $this->deliveryCity = (string) auth()->user()->city;
    }

    protected function rules(): array
    {
        return [
            'quantityText' => ['required', 'string', 'max:80'],
            'contactPhone' => ['required', 'string', 'max:40'],
            'deliveryAddress' => ['required', 'string', 'max:255'],
            'deliveryCity' => ['required', 'string', 'max:120'],
            'message' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function placeOrder()
    {
        $this->validate();
        abort_unless(auth()->user()->isActive(), 403);

        // Anti-spam : une seule demande ouverte par (acheteur, annonce).
        $exists = auth()->user()->orders()
            ->where('orderable_type', $this->listing->getMorphClass())
            ->where('orderable_id', $this->listing->id)
            ->whereIn('status', ['validee', 'expediee'])
            ->exists();

        if ($exists) {
            $this->addError('quantityText', 'Vous avez déjà une demande en cours pour cette annonce.');

            return;
        }

        $order = auth()->user()->orders()->create([
            'customer_name' => auth()->user()->name,
            'contact_phone' => $this->contactPhone,
            'delivery_address' => $this->deliveryAddress,
            'delivery_city' => $this->deliveryCity,
            'orderable_type' => $this->listing->getMorphClass(),
            'orderable_id' => $this->listing->id,
            'item_label' => $this->listing->title.' — '.$this->quantityText,
            'quantity' => 1,
            'amount' => 0,
            'delivery_fee' => 0,
            'customer_note' => $this->message,
            'payment_mode' => OrderPaymentMode::Direct,
            'status' => OrderStatus::Validee,
        ]);

        $this->notify($order);

        session()->flash('flash', "Demande {$order->reference} envoyée. Le producteur va vous contacter.");

        return $this->redirectRoute('learner.orders', navigate: true);
    }

    public function sellerWhatsapp(): ?string
    {
        $phone = $this->listing->seller?->phone ?: PaymentSetting::current()->whatsapp;
        $number = preg_replace('/\D+/', '', (string) $phone);
        if ($number === '') {
            return null;
        }
        if (strlen($number) <= 10) {
            $number = '225'.ltrim($number, '0');
        }

        return 'https://wa.me/'.$number.'?text='.rawurlencode(
            "Bonjour, je suis intéressé(e) par votre annonce « {$this->listing->title} » sur Le Roi du Manioc."
        );
    }

    private function notify(Order $order): void
    {
        try {
            Mail::to($order->user->email)->send(new OrderUpdateMail($order, 'placed'));

            if ($this->listing->seller?->email) {
                Mail::to($this->listing->seller->email)->send(new ListingOrderMail($order));
            }

            $adminEmail = User::where('role', 'admin')->where('status', 'actif')->value('email')
                ?: config('mail.from.address');
            Mail::to($adminEmail)->send(new NewOrderMail($order));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function render()
    {
        return view('livewire.learner.listing-checkout');
    }
}

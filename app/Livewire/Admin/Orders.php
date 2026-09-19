<?php

namespace App\Livewire\Admin;

use App\Enums\OrderStatus;
use App\Mail\OrderUpdateMail;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class Orders extends Component
{
    use WithPagination;

    #[Url]
    public string $filter = 'a_traiter';

    public ?int $expanded = null;

    public ?int $cancelling = null;

    /** Colonnes triables (audit architecture) — liste blanche : $sort vient de l'URL, jamais fiable tel quel. */
    private const SORTABLE = ['reference', 'amount', 'ordered_at'];

    #[Url]
    public string $sort = 'ordered_at';

    #[Url]
    public string $direction = 'desc';

    public function sortBy(string $field): void
    {
        if (! in_array($field, self::SORTABLE, true)) {
            return;
        }

        $this->direction = $this->sort === $field && $this->direction === 'asc' ? 'desc' : 'asc';
        $this->sort = $field;
    }

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
        $this->resetPage();
    }

    public function toggle(int $id): void
    {
        $this->expanded = $this->expanded === $id ? null : $id;
    }

    public function startCancel(int $id): void
    {
        $this->cancelling = $this->cancelling === $id ? null : $id;
    }

    public function ship(int $orderId): void
    {
        $order = Order::where('status', OrderStatus::Validee)->findOrFail($orderId);
        $order->markShipped(auth()->user());
        $this->mailClient($order, 'shipped');
        $this->dispatch('notify', message: "Commande {$order->reference} en livraison.");
    }

    public function deliver(int $orderId): void
    {
        $order = Order::whereIn('status', [OrderStatus::Expediee, OrderStatus::Validee])->findOrFail($orderId);
        $order->markDelivered(auth()->user());
        $this->mailClient($order, 'delivered');
        $this->dispatch('notify', message: "Commande {$order->reference} livrée.");
    }

    public function cancel(int $orderId, string $reason = 'other'): void
    {
        $order = Order::with('user')->findOrFail($orderId);

        if (! $order->status->isOpen()) {
            return;
        }

        $order->cancel(auth()->user());

        // Livraison refusée par le client -> un « strike » (2 = plus de paiement à la livraison).
        if ($reason === 'no_show' && $order->user) {
            $order->user->increment('delivery_strikes');
            activity('order')->performedOn($order)->causedBy(auth()->user())->log('cancelled_no_show');
        }

        $this->mailClient($order, 'cancelled');
        $this->cancelling = null;
        $this->dispatch('notify', message: "Commande {$order->reference} annulée. Stock réintégré.");
    }

    private function mailClient(Order $order, string $stage): void
    {
        if (! $order->user) {
            return;
        }

        try {
            Mail::to($order->user->email)->send(new OrderUpdateMail($order->fresh(), $stage));
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function render()
    {
        // $sort/$direction viennent de l'URL (#[Url]) : jamais utilisés tels quels dans la requête.
        $sort = in_array($this->sort, self::SORTABLE, true) ? $this->sort : 'ordered_at';
        $direction = $this->direction === 'asc' ? 'asc' : 'desc';

        $query = Order::with(['user', 'payment', 'handledBy', 'orderable'])->orderBy($sort, $direction);

        match ($this->filter) {
            'a_traiter' => $query->whereIn('status', [OrderStatus::Paiement, OrderStatus::Validee]),
            'expediee' => $query->where('status', OrderStatus::Expediee),
            'livree' => $query->where('status', OrderStatus::Livree),
            'annulee' => $query->where('status', OrderStatus::Refuse),
            default => null, // tous
        };

        return view('livewire.admin.orders', [
            'orders' => $query->paginate(20),
            'counts' => [
                'a_traiter' => Order::whereIn('status', [OrderStatus::Paiement, OrderStatus::Validee])->count(),
                'expediee' => Order::where('status', OrderStatus::Expediee)->count(),
                'on_delivery' => Order::where('payment_mode', 'on_delivery')->whereIn('status', [OrderStatus::Validee, OrderStatus::Expediee])->count(),
            ],
        ]);
    }
}

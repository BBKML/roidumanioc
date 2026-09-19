<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderUpdateMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param 'placed'|'shipped'|'delivered'|'cancelled' $stage */
    public function __construct(public Order $order, public string $stage) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->stage) {
            'placed' => 'Commande '.$this->order->reference.' bien reçue',
            'shipped' => 'Commande '.$this->order->reference.' — en route',
            'delivered' => 'Commande '.$this->order->reference.' livrée',
            'cancelled' => 'Commande '.$this->order->reference.' annulée',
            default => 'Commande '.$this->order->reference,
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.order.update', with: [
            'order' => $this->order,
            'stage' => $this->stage,
        ]);
    }
}

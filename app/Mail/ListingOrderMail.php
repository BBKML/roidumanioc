<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Prévient le producteur qu'un acheteur souhaite son annonce.
 */
class ListingOrderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Un acheteur vous contacte — '.$this->order->item_label);
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.order.listing', with: ['order' => $this->order]);
    }
}

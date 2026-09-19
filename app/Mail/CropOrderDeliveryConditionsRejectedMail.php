<?php

namespace App\Mail;

use App\Models\CropOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CropOrderDeliveryConditionsRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public CropOrder $cropOrder, public string $reason) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Conditions de livraison renvoyées');
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.crop-order.delivery-conditions-rejected',
            with: ['cropOrder' => $this->cropOrder, 'reason' => $this->reason],
        );
    }
}

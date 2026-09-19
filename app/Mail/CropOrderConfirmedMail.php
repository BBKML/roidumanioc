<?php

namespace App\Mail;

use App\Models\CropOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CropOrderConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public CropOrder $cropOrder) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Commande confirmée');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.crop-order.confirmed', with: ['cropOrder' => $this->cropOrder]);
    }
}

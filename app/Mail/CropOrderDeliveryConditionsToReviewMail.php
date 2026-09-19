<?php

namespace App\Mail;

use App\Models\CropOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CropOrderDeliveryConditionsToReviewMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public CropOrder $cropOrder) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Conditions de livraison à valider');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.crop-order.delivery-conditions-to-review', with: ['cropOrder' => $this->cropOrder]);
    }
}

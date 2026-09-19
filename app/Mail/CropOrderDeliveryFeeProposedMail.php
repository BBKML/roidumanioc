<?php

namespace App\Mail;

use App\Models\CropOrder;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CropOrderDeliveryFeeProposedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public CropOrder $cropOrder, public User $actor, public bool $isFirst) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->isFirst ? 'Conditions de livraison reçues' : 'Nouvelle proposition de frais de livraison',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.crop-order.delivery-fee-proposed',
            with: ['cropOrder' => $this->cropOrder, 'actor' => $this->actor, 'isFirst' => $this->isFirst],
        );
    }
}

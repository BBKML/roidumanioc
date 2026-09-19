<?php

namespace App\Mail;

use App\Models\Collaboration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CollaborationPaymentDeclaredMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Collaboration $collaboration) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Paiement déclaré par votre acheteur');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.connect.payment-declared', with: ['collaboration' => $this->collaboration]);
    }
}

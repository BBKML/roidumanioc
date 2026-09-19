<?php

namespace App\Mail;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Payment $payment) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Paiement '.$this->payment->reference.' confirmé — accès ouvert');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.payment.confirmed', with: ['payment' => $this->payment]);
    }
}

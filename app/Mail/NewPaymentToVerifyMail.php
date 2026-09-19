<?php

namespace App\Mail;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewPaymentToVerifyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Payment $payment) {}

    public function envelope(): Envelope
    {
        $risk = strtoupper($this->payment->risk());

        return new Envelope(subject: "[{$risk}] Paiement à vérifier — {$this->payment->reference}");
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.payment.to-verify', with: ['payment' => $this->payment]);
    }
}

<?php

namespace App\Mail;

use App\Models\RegistrationLead;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegistrationLeadMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public RegistrationLead $lead) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nouvelle inscription — '.$this->lead->registrationForm->title,
            replyTo: [$this->lead->email],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.registration-lead',
            with: ['lead' => $this->lead],
        );
    }
}

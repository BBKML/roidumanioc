<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ContactMessage $contactMessage,
        public string $body,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Re : '.($this->contactMessage->subject ?: 'votre message'),
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.contact-reply', with: [
            'message' => $this->contactMessage,
            'body' => $this->body,
        ]);
    }
}

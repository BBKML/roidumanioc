<?php

namespace App\Mail;

use App\Models\ConnectionRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewConnectionRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ConnectionRequest $connectionRequest) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Nouvelle demande de mise en relation');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.connect.new-request', with: ['connectionRequest' => $this->connectionRequest]);
    }
}

<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MemberPasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public string $password) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Votre mot de passe a été réinitialisé');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.member.password-reset', with: [
            'user' => $this->user,
            'password' => $this->password,
        ]);
    }
}

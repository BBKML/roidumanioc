<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Mail\ContactMessageMail;
use App\Models\ContactMessage;
use App\Models\SiteContent;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function show()
    {
        return view('contact', [
            'content' => SiteContent::payload(),
        ]);
    }

    public function send(StoreContactRequest $request)
    {
        $message = ContactMessage::create([
            ...$request->safe()->only(['name', 'email', 'phone', 'subject', 'message']),
            'ip' => $request->ip(),
        ]);

        $to = data_get(SiteContent::section('pied'), 'email') ?: config('mail.from.address');

        // On n'interrompt jamais l'utilisateur si le SMTP tombe : le message est déjà en base.
        try {
            Mail::to($to)->cc(User::activeAdminEmails($to))->send(new ContactMessageMail($message));
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()
            ->route('contact')
            ->with('contact_sent', 'Merci, votre message est bien parti. Nous revenons vers vous rapidement.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:180'],
            'website' => ['prohibited'], // honeypot
        ]);

        $subscriber = NewsletterSubscriber::firstOrNew(['email' => mb_strtolower($data['email'])]);
        $subscriber->source ??= 'footer';
        $subscriber->unsubscribed_at = null; // (ré)active un e-mail déjà désinscrit
        $subscriber->save();

        return back()->with('newsletter', 'Merci ! Vous êtes inscrit à nos conseils manioc.');
    }
}

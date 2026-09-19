<?php

namespace App\Listeners;

use App\Events\CropOrderCreated;
use App\Mail\NewCropOrderMail;
use App\Notifications\CropOrderCreatedNotification;
use Illuminate\Support\Facades\Mail;

/**
 * « Nouvelle commande » : in-app systématique + e-mail (fort enjeu), jamais bloquant si le
 * SMTP tombe. Auto-découvert par Laravel (pas d'EventServiceProvider dans ce projet).
 */
class SendCropOrderCreatedNotifications
{
    public function handle(CropOrderCreated $event): void
    {
        $recipient = $event->cropOrder->producerProfile->user;

        $recipient->notify(new CropOrderCreatedNotification($event->cropOrder));

        if (! $recipient->email) {
            return;
        }

        try {
            Mail::to($recipient->email)->send(new NewCropOrderMail($event->cropOrder));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}

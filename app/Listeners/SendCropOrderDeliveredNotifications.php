<?php

namespace App\Listeners;

use App\Events\CropOrderDelivered;
use App\Mail\DeliveryDoneMail;
use App\Notifications\CropOrderDeliveredNotification;
use Illuminate\Support\Facades\Mail;

/** L'acheteur vient de confirmer — seul le producteur (l'autre partie) est notifié. */
class SendCropOrderDeliveredNotifications
{
    public function handle(CropOrderDelivered $event): void
    {
        $recipient = $event->cropOrder->otherParty($event->actor);

        $recipient->notify(new CropOrderDeliveredNotification($event->cropOrder));

        if (! $recipient->email) {
            return;
        }

        try {
            Mail::to($recipient->email)->send(new DeliveryDoneMail($event->cropOrder));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}

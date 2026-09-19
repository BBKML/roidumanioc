<?php

namespace App\Listeners;

use App\Events\CropOrderConfirmed;
use App\Mail\CropOrderConfirmedMail;
use App\Notifications\CropOrderConfirmedNotification;
use Illuminate\Support\Facades\Mail;

/** Les DEUX parties doivent voir les conditions figées — notifiées toutes les deux. */
class SendCropOrderConfirmedNotifications
{
    public function handle(CropOrderConfirmed $event): void
    {
        $recipients = [$event->cropOrder->producerProfile->user, $event->cropOrder->buyerProfile->user];

        foreach ($recipients as $recipient) {
            $recipient->notify(new CropOrderConfirmedNotification($event->cropOrder));

            if (! $recipient->email) {
                continue;
            }

            try {
                Mail::to($recipient->email)->send(new CropOrderConfirmedMail($event->cropOrder));
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}

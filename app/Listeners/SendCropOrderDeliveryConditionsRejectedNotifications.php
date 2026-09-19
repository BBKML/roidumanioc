<?php

namespace App\Listeners;

use App\Events\CropOrderDeliveryConditionsRejected;
use App\Mail\CropOrderDeliveryConditionsRejectedMail;
use App\Notifications\CropOrderDeliveryConditionsRejectedNotification;
use Illuminate\Support\Facades\Mail;

class SendCropOrderDeliveryConditionsRejectedNotifications
{
    public function handle(CropOrderDeliveryConditionsRejected $event): void
    {
        $recipient = $event->cropOrder->producerProfile->user;

        $recipient->notify(new CropOrderDeliveryConditionsRejectedNotification($event->cropOrder, $event->reason));

        if (! $recipient->email) {
            return;
        }

        try {
            Mail::to($recipient->email)->send(new CropOrderDeliveryConditionsRejectedMail($event->cropOrder, $event->reason));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}

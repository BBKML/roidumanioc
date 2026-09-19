<?php

namespace App\Listeners;

use App\Enums\DeliveryAssistStatus;
use App\Events\CropOrderDeliveryAssistStepMarked;
use App\Mail\DeliveryDoneMail;
use App\Notifications\CropOrderDeliveryAssistStepMarkedNotification;
use Illuminate\Support\Facades\Mail;

/** E-mail seulement à l'étape finale (« livrée ») — fort enjeu, le reste reste in-app seul. */
class SendCropOrderDeliveryAssistStepMarkedNotifications
{
    public function handle(CropOrderDeliveryAssistStepMarked $event): void
    {
        $recipients = [$event->cropOrder->producerProfile->user, $event->cropOrder->buyerProfile->user];

        foreach ($recipients as $recipient) {
            $recipient->notify(new CropOrderDeliveryAssistStepMarkedNotification($event->cropOrder, $event->to));

            if ($event->to !== DeliveryAssistStatus::Livree || ! $recipient->email) {
                continue;
            }

            try {
                Mail::to($recipient->email)->send(new DeliveryDoneMail($event->cropOrder));
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}

<?php

namespace App\Listeners;

use App\Events\CropOrderDeliveryFeeProposed;
use App\Mail\CropOrderDeliveryFeeProposedMail;
use App\Notifications\CropOrderDeliveryFeeProposedNotification;
use Illuminate\Support\Facades\Mail;

/** Un montant réel est en jeu à chaque fois — traité comme fort enjeu (in-app + e-mail). */
class SendCropOrderDeliveryFeeProposedNotifications
{
    public function handle(CropOrderDeliveryFeeProposed $event): void
    {
        $recipient = $event->cropOrder->otherParty($event->actor);

        $recipient->notify(new CropOrderDeliveryFeeProposedNotification($event->cropOrder, $event->actor, $event->isFirst));

        if (! $recipient->email) {
            return;
        }

        try {
            Mail::to($recipient->email)->send(new CropOrderDeliveryFeeProposedMail($event->cropOrder, $event->actor, $event->isFirst));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}

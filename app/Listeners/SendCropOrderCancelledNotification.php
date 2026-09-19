<?php

namespace App\Listeners;

use App\Events\CropOrderCancelled;
use App\Notifications\CropOrderCancelledNotification;

class SendCropOrderCancelledNotification
{
    public function handle(CropOrderCancelled $event): void
    {
        $recipient = $event->cropOrder->otherParty($event->actor);

        $recipient->notify(new CropOrderCancelledNotification($event->cropOrder, $event->actor));
    }
}

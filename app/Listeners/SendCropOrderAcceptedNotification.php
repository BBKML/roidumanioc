<?php

namespace App\Listeners;

use App\Events\CropOrderAccepted;
use App\Notifications\CropOrderAcceptedNotification;

class SendCropOrderAcceptedNotification
{
    public function handle(CropOrderAccepted $event): void
    {
        $event->cropOrder->buyerProfile->user->notify(new CropOrderAcceptedNotification($event->cropOrder));
    }
}

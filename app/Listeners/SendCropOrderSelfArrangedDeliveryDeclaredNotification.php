<?php

namespace App\Listeners;

use App\Events\CropOrderSelfArrangedDeliveryDeclared;
use App\Notifications\CropOrderSelfArrangedDeliveryDeclaredNotification;

class SendCropOrderSelfArrangedDeliveryDeclaredNotification
{
    public function handle(CropOrderSelfArrangedDeliveryDeclared $event): void
    {
        $event->cropOrder->otherParty($event->actor)
            ->notify(new CropOrderSelfArrangedDeliveryDeclaredNotification($event->cropOrder, $event->actor, $event->mode));
    }
}

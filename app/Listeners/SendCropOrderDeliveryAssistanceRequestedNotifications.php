<?php

namespace App\Listeners;

use App\Events\CropOrderDeliveryAssistanceRequested;
use App\Mail\NewDeliveryAssistRequestMail;
use App\Models\User;
use App\Notifications\CropOrderDeliveryAssistanceRequestedNotification;
use Illuminate\Support\Facades\Mail;

class SendCropOrderDeliveryAssistanceRequestedNotifications
{
    public function handle(CropOrderDeliveryAssistanceRequested $event): void
    {
        $event->cropOrder->otherParty($event->actor)
            ->notify(new CropOrderDeliveryAssistanceRequestedNotification($event->cropOrder, $event->actor));

        $adminEmails = User::activeAdminEmails();
        if ($adminEmails === []) {
            return;
        }

        try {
            Mail::to($adminEmails)->send(new NewDeliveryAssistRequestMail($event->cropOrder));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}

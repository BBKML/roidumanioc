<?php

namespace App\Listeners;

use App\Events\CropOrderRefused;
use App\Mail\CropOrderRefusedMail;
use App\Notifications\CropOrderRefusedNotification;
use Illuminate\Support\Facades\Mail;

class SendCropOrderRefusedNotifications
{
    public function handle(CropOrderRefused $event): void
    {
        $recipient = $event->cropOrder->buyerProfile->user;

        $recipient->notify(new CropOrderRefusedNotification($event->cropOrder));

        if (! $recipient->email) {
            return;
        }

        try {
            Mail::to($recipient->email)->send(new CropOrderRefusedMail($event->cropOrder));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}

<?php

namespace App\Listeners;

use App\Events\CropOrderDeliveryConditionsSubmittedForReview;
use App\Mail\CropOrderDeliveryConditionsToReviewMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/** Même patron que SendCropOrderDeliveryAssistanceRequestedNotifications : admin uniquement, par e-mail. */
class SendCropOrderDeliveryConditionsSubmittedNotifications
{
    public function handle(CropOrderDeliveryConditionsSubmittedForReview $event): void
    {
        $adminEmails = User::activeAdminEmails();
        if ($adminEmails === []) {
            return;
        }

        try {
            Mail::to($adminEmails)->send(new CropOrderDeliveryConditionsToReviewMail($event->cropOrder));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}

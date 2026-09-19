<?php

namespace App\Listeners;

use App\Events\ReviewSubmitted;
use App\Notifications\ReviewSubmittedNotification;

/** Phase 9 (§24) — « nouvelle évaluation » : in-app uniquement. Notifie l'évalué. */
class SendReviewSubmittedNotification
{
    public function handle(ReviewSubmitted $event): void
    {
        $event->review->ratee->notify(new ReviewSubmittedNotification($event->review));
    }
}

<?php

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Notifications\Notification;

/** Phase 9 (§24) — in-app uniquement, pas dans la liste des événements à fort enjeu. */
class ReviewSubmittedNotification extends Notification
{
    public function __construct(public Review $review) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'review_submitted',
            'title' => 'Nouvelle évaluation',
            'message' => sprintf(
                '%s vous a laissé une évaluation (%d/5) pour « %s ».',
                $this->review->rater->name,
                $this->review->rating,
                $this->review->collaboration->agreed_product,
            ),
            'url' => route('learner.collaborations.show', $this->review->collaboration),
        ];
    }
}

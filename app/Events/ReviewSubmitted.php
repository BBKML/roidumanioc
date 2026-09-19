<?php

namespace App\Events;

use App\Models\Review;

/**
 * Complète le point d'extension laissé par la Phase 8 — branché à la Phase 9.
 */
class ReviewSubmitted
{
    public function __construct(public Review $review) {}
}

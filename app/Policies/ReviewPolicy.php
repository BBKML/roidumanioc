<?php

namespace App\Policies;

use App\Models\Collaboration;
use App\Models\User;

/**
 * `create` exclu du contournement admin (AppServiceProvider::boot()) — un administrateur
 * n'est jamais partie à une collaboration, il ne doit donc jamais pouvoir déposer un avis.
 */
class ReviewPolicy
{
    public function create(User $user, Collaboration $collaboration): bool
    {
        return $collaboration->canBeReviewedBy($user);
    }
}

<?php

namespace App\Policies;

use App\Models\BuyerProfile;
use App\Models\User;

/**
 * Un administrateur passe tout (voir Gate::before dans AppServiceProvider).
 */
class BuyerProfilePolicy
{
    public function update(User $user, BuyerProfile $profile): bool
    {
        return $user->id === $profile->user_id;
    }
}

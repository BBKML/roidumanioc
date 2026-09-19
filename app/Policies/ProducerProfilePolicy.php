<?php

namespace App\Policies;

use App\Models\ProducerProfile;
use App\Models\User;

/**
 * Un administrateur passe tout (voir Gate::before dans AppServiceProvider).
 */
class ProducerProfilePolicy
{
    public function update(User $user, ProducerProfile $profile): bool
    {
        return $user->id === $profile->user_id;
    }
}

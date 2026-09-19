<?php

namespace App\Policies;

use App\Models\BuyerNeed;
use App\Models\User;

/**
 * Un administrateur passe tout (voir Gate::before dans AppServiceProvider).
 */
class BuyerNeedPolicy
{
    public function update(User $user, BuyerNeed $need): bool
    {
        return $need->buyerProfile->user_id === $user->id;
    }

    public function delete(User $user, BuyerNeed $need): bool
    {
        return $this->update($user, $need);
    }
}

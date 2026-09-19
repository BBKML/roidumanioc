<?php

namespace App\Policies;

use App\Models\CropOffer;
use App\Models\User;

/**
 * Un administrateur passe tout (voir Gate::before dans AppServiceProvider).
 */
class CropOfferPolicy
{
    public function update(User $user, CropOffer $offer): bool
    {
        return $offer->producerProfile->user_id === $user->id;
    }

    public function delete(User $user, CropOffer $offer): bool
    {
        return $this->update($user, $offer);
    }
}

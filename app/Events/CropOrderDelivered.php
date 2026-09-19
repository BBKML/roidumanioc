<?php

namespace App\Events;

use App\Models\CropOrder;
use App\Models\User;

/** Livraison auto-organisée confirmée par l'acheteur (`$actor`) — distinct de la livraison assistée par l'admin. */
class CropOrderDelivered
{
    public function __construct(public CropOrder $cropOrder, public User $actor) {}
}

<?php

namespace App\Events;

use App\Models\CropOrder;
use App\Models\User;

/**
 * Fired both by setDeliveryConditions() (première proposition, $isFirst = true) et
 * proposeDeliveryFee() (contre-proposition, $isFirst = false) — porte `$actor` car les
 * deux parties peuvent proposer, il faut savoir qui pour notifier l'AUTRE.
 */
class CropOrderDeliveryFeeProposed
{
    public function __construct(public CropOrder $cropOrder, public User $actor, public bool $isFirst = false) {}
}

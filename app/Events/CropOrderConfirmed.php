<?php

namespace App\Events;

use App\Models\CropOrder;
use App\Models\User;

/** Porte `$actor` (celui qui a accepté les frais de livraison) — les DEUX parties sont notifiées. */
class CropOrderConfirmed
{
    public function __construct(public CropOrder $cropOrder, public User $actor) {}
}

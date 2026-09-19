<?php

namespace App\Events;

use App\Models\CropOrder;
use App\Models\User;

/** `$mode` : 'acheteur' (le client récupère / a son propre livreur) ou 'producteur' (le producteur livre lui-même). */
class CropOrderSelfArrangedDeliveryDeclared
{
    public function __construct(public CropOrder $cropOrder, public User $actor, public string $mode) {}
}

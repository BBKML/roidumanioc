<?php

namespace App\Events;

use App\Models\CropOrder;
use App\Models\User;

class CropOrderCancelled
{
    public function __construct(public CropOrder $cropOrder, public User $actor) {}
}

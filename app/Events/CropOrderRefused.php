<?php

namespace App\Events;

use App\Models\CropOrder;

class CropOrderRefused
{
    public function __construct(public CropOrder $cropOrder) {}
}

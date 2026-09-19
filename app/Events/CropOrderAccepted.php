<?php

namespace App\Events;

use App\Models\CropOrder;

class CropOrderAccepted
{
    public function __construct(public CropOrder $cropOrder) {}
}

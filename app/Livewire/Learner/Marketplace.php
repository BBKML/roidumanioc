<?php

namespace App\Livewire\Learner;

use App\Models\MarketplaceListing;
use App\Models\ShopProduct;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.learner')]
class Marketplace extends Component
{
    public function render()
    {
        return view('livewire.learner.marketplace', [
            'listings' => MarketplaceListing::published()->with('seller')->latest()->get(),
            'products' => ShopProduct::active()->orderBy('position')->get(),
        ]);
    }
}

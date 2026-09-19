<?php

namespace App\Livewire\Buyer;

use App\Models\BuyerNeed;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.learner')]
class Needs extends Component
{
    use WithPagination;

    public function delete(BuyerNeed $need): void
    {
        $this->authorize('delete', $need);

        $need->delete();

        $this->dispatch('notify', message: 'Besoin supprimé.');
    }

    public function render()
    {
        $needs = Auth::user()->buyerProfile->buyerNeeds()
            ->latest()
            ->paginate(9);

        return view('livewire.buyer.needs', compact('needs'));
    }
}

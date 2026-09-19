<?php

namespace App\Livewire\Admin;

use App\Models\Conversation;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Modération : liste des conversations ayant déclenché au moins un signal de détection
 * de coordonnées (§16). Lecture seule — l'admin ne participe jamais à la conversation
 * (ConversationPolicy::send() l'exclut explicitement, cf. AppServiceProvider::boot()).
 */
#[Layout('components.layouts.admin')]
class Conversations extends Component
{
    use WithPagination;

    public function render()
    {
        $conversations = Conversation::query()
            ->whereHas('messages', fn ($q) => $q->where('contains_flagged_content', true))
            ->withCount(['messages as flagged_count' => fn ($q) => $q->where('contains_flagged_content', true)])
            ->with(['connectionRequest.producerProfile', 'connectionRequest.buyerProfile'])
            ->latest('updated_at')
            ->paginate(20);

        return view('livewire.admin.conversations', compact('conversations'));
    }
}

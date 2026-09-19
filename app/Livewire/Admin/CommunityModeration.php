<?php

namespace App\Livewire\Admin;

use App\Enums\PostStatus;
use App\Models\CommunityPost;
use App\Models\CommunityReply;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Liste en cartes (pas un <table>), choix délibéré : chaque question porte un texte libre
 * et ses réponses imbriquées — du contenu trop riche pour des colonnes. Même raisonnement
 * que Payments/Messages/RegistrationLeads (audit architecture) : les écrans "fiche à
 * consulter" restent en cartes, les écrans "liste de champs comparables" restent en
 * <table>.
 */
#[Layout('components.layouts.admin')]
class CommunityModeration extends Component
{
    #[Url]
    public string $filter = 'tous';

    public ?int $openPostId = null;

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
        $this->openPostId = null;
    }

    public function toggleReplies(int $postId): void
    {
        $this->openPostId = $this->openPostId === $postId ? null : $postId;
    }

    public function hide(CommunityPost $post): void
    {
        $post->update(['status' => PostStatus::Masque]);
        $this->dispatch('notify', message: 'Question masquée.');
    }

    public function show(CommunityPost $post): void
    {
        $post->update(['status' => PostStatus::Visible]);
        $this->dispatch('notify', message: 'Question de nouveau visible.');
    }

    public function deletePost(CommunityPost $post): void
    {
        $post->replies()->delete();
        $post->delete();
        $this->dispatch('notify', message: 'Question supprimée.');
    }

    public function deleteReply(CommunityReply $reply): void
    {
        $post = $reply->post;
        $reply->delete();
        $post?->update(['replies_count' => max(0, $post->replies()->count())]);
        $this->dispatch('notify', message: 'Réponse supprimée.');
    }

    public function render()
    {
        $query = CommunityPost::withCount('replies')->latest();

        if (in_array($this->filter, ['visible', 'masque', 'signale'], true)) {
            $query->where('status', $this->filter);
        }

        return view('livewire.admin.community-moderation', [
            'posts' => $query->get(),
            'replies' => $this->openPostId
                ? CommunityReply::where('community_post_id', $this->openPostId)->oldest()->get()
                : collect(),
            'counts' => [
                'signale' => CommunityPost::where('status', 'signale')->count(),
                'masque' => CommunityPost::where('status', 'masque')->count(),
                'visible' => CommunityPost::where('status', 'visible')->count(),
            ],
        ]);
    }
}

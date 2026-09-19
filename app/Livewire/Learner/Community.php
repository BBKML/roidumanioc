<?php

namespace App\Livewire\Learner;

use App\Enums\PostStatus;
use App\Models\CommunityPost;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('components.layouts.learner')]
class Community extends Component
{
    #[Validate('required|string|min:5|max:2000')]
    public string $body = '';

    public ?int $replyTo = null;

    #[Validate('required|string|min:2|max:2000')]
    public string $replyBody = '';

    public function publish(): void
    {
        $this->validateOnly('body');

        auth()->user()->communityPosts()->create([
            'author_name' => auth()->user()->name,
            'body' => $this->body,
            'status' => PostStatus::Visible,
        ]);

        $this->reset('body');
        $this->dispatch('notify', message: 'Votre message est publié.');
    }

    public function startReply(int $postId): void
    {
        $this->replyTo = $this->replyTo === $postId ? null : $postId;
        $this->replyBody = '';
        $this->resetValidation();
    }

    public function sendReply(): void
    {
        $this->validateOnly('replyBody');

        $post = CommunityPost::visible()->findOrFail($this->replyTo);

        $post->replies()->create([
            'user_id' => auth()->id(),
            'author_name' => auth()->user()->name,
            'body' => $this->replyBody,
            'status' => 'visible',
        ]);
        $post->update(['replies_count' => $post->replies()->count()]);

        $this->reset('replyBody', 'replyTo');
        $this->dispatch('notify', message: 'Réponse envoyée.');
    }

    public function render()
    {
        return view('livewire.learner.community', [
            'posts' => CommunityPost::visible()
                ->with(['replies' => fn ($q) => $q->where('status', 'visible')->oldest()])
                ->latest()
                ->get(),
        ]);
    }
}

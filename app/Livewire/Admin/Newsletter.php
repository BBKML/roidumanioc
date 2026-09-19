<?php

namespace App\Livewire\Admin;

use App\Models\NewsletterSubscriber;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.admin')]
class Newsletter extends Component
{
    use WithPagination;

    #[Url]
    public string $filter = 'actifs';

    #[Url]
    public string $search = '';

    public string $newEmail = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
        $this->resetPage();
    }

    public function add(): void
    {
        $data = $this->validate([
            'newEmail' => ['required', 'email', 'max:180', Rule::unique('newsletter_subscribers', 'email')],
        ]);

        NewsletterSubscriber::create([
            'email' => mb_strtolower($data['newEmail']),
            'source' => 'admin',
        ]);

        $this->reset('newEmail');
        $this->dispatch('notify', message: 'Abonné ajouté.');
    }

    public function unsubscribe(int $id): void
    {
        NewsletterSubscriber::findOrFail($id)->unsubscribe();
        $this->dispatch('notify', message: 'Abonné désinscrit.');
    }

    public function resubscribe(int $id): void
    {
        NewsletterSubscriber::findOrFail($id)->resubscribe();
        $this->dispatch('notify', message: 'Abonné réinscrit.');
    }

    public function delete(int $id): void
    {
        NewsletterSubscriber::findOrFail($id)->delete();
        $this->dispatch('notify', message: 'Abonné supprimé.');
    }

    public function render()
    {
        $query = NewsletterSubscriber::latest();

        match ($this->filter) {
            'actifs' => $query->active(),
            'desinscrits' => $query->whereNotNull('unsubscribed_at'),
            default => null,
        };

        if ($this->search !== '') {
            $query->where('email', 'like', "%{$this->search}%");
        }

        return view('livewire.admin.newsletter', [
            'subscribers' => $query->paginate(30),
            'stats' => [
                'active' => NewsletterSubscriber::active()->count(),
                'unsubscribed' => NewsletterSubscriber::whereNotNull('unsubscribed_at')->count(),
            ],
        ]);
    }
}

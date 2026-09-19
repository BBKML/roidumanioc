<?php

namespace App\Livewire\Admin;

use App\Enums\ContactMessageStatus;
use App\Mail\ContactReplyMail;
use App\Models\ContactMessage;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Liste en cartes (pas un <table>), choix délibéré : chaque message porte un texte libre,
 * une réponse dépliable et une note interne — du contenu trop riche pour des colonnes.
 * Même raisonnement que Payments/RegistrationLeads/CommunityModeration (audit
 * architecture) : les écrans "fiche à consulter" restent en cartes, les écrans "liste de
 * champs comparables" restent en <table>.
 */
#[Layout('components.layouts.admin')]
class Messages extends Component
{
    use WithPagination;

    #[Url]
    public string $filter = 'inbox';

    #[Url]
    public string $search = '';

    public ?int $openId = null;

    public string $replyBody = '';

    public string $note = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
        $this->openId = null;
        $this->resetPage();
    }

    /** Recherche/filtre sans résultat (audit architecture) : un vrai bouton pour repartir de zéro. */
    public function resetFilters(): void
    {
        $this->reset('search', 'filter');
    }

    public function open(int $id): void
    {
        if ($this->openId === $id) {
            $this->openId = null;

            return;
        }

        $this->openId = $id;
        $this->reset('replyBody');
        $message = ContactMessage::findOrFail($id);
        $this->note = (string) $message->admin_note;
        $message->markRead();
    }

    public function saveNote(int $id): void
    {
        ContactMessage::findOrFail($id)->update(['admin_note' => $this->note ?: null]);
        $this->dispatch('notify', message: 'Note enregistrée.');
    }

    public function reply(int $id): void
    {
        $this->validate(['replyBody' => ['required', 'string', 'min:5', 'max:5000']]);

        $message = ContactMessage::findOrFail($id);

        try {
            Mail::to($message->email)->send(new ContactReplyMail($message, $this->replyBody));
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('notify', message: "L'e-mail n'a pas pu partir. Réessayez ou répondez depuis votre boîte.");

            return;
        }

        $message->markHandled(auth()->user(), replied: true);
        $this->reset('replyBody');
        $this->dispatch('notify', message: "Réponse envoyée à {$message->email}.");
    }

    public function markHandled(int $id): void
    {
        ContactMessage::findOrFail($id)->markHandled(auth()->user());
        $this->dispatch('notify', message: 'Message marqué traité.');
    }

    public function markSpam(int $id): void
    {
        ContactMessage::findOrFail($id)->markSpam(auth()->user());
        $this->dispatch('notify', message: 'Message classé en spam.');
    }

    public function reopen(int $id): void
    {
        ContactMessage::findOrFail($id)->reopen();
        $this->dispatch('notify', message: 'Message rouvert.');
    }

    public function delete(int $id): void
    {
        ContactMessage::findOrFail($id)->delete();
        $this->openId = null;
        $this->dispatch('notify', message: 'Message supprimé.');
    }

    public function render()
    {
        $query = ContactMessage::with('handledBy')->latest();

        match ($this->filter) {
            'inbox' => $query->inbox(),
            'traite' => $query->where('status', ContactMessageStatus::Traite),
            'spam' => $query->where('status', ContactMessageStatus::Spam),
            default => null,
        };

        if ($this->search !== '') {
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")
                ->orWhere('subject', 'like', "%{$this->search}%")
                ->orWhere('message', 'like', "%{$this->search}%"));
        }

        return view('livewire.admin.messages', [
            'messages' => $query->paginate(15),
            'counts' => [
                'inbox' => ContactMessage::inbox()->count(),
                'traite' => ContactMessage::where('status', ContactMessageStatus::Traite)->count(),
                'spam' => ContactMessage::where('status', ContactMessageStatus::Spam)->count(),
            ],
        ]);
    }
}

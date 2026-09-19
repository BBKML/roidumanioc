<?php

namespace App\Livewire\Admin;

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.admin')]
class Events extends Component
{
    use WithFileUploads;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $title = '';

    public string $date_label = '';

    public ?string $starts_at = null;

    public string $type = 'Live';

    public string $status = 'planifie';

    public ?string $link = null;

    public ?string $description = null;

    /** Image en attente de téléversement (formulaire ouvert). */
    public $image = null;

    /** Chemin de l'image déjà enregistrée (aperçu + gardée si aucune nouvelle n'est choisie). */
    public ?string $image_path = null;

    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'date_label' => ['nullable', 'string', 'max:60'],
            'starts_at' => ['nullable', 'date'],
            'type' => ['required', 'string', 'max:40'],
            'status' => ['required', 'in:planifie,termine'],
            'link' => ['nullable', 'url', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'max:4096'],
        ];
    }

    public function new(): void
    {
        $this->reset('editingId', 'title', 'date_label', 'starts_at', 'link', 'description', 'image', 'image_path');
        $this->type = 'Live';
        $this->status = 'planifie';
        $this->resetValidation();
        $this->showForm = true;
    }

    public function edit(Event $event): void
    {
        $this->editingId = $event->id;
        $this->title = $event->title;
        $this->date_label = (string) $event->date_label;
        $this->starts_at = $event->starts_at?->format('Y-m-d');
        $this->type = $event->type;
        $this->status = $event->status->value;
        $this->link = $event->link;
        $this->description = $event->description;
        $this->image = null;
        $this->image_path = $event->image_path;
        $this->resetValidation();
        $this->showForm = true;
    }

    /** Retire l'image (effectif à l'enregistrement, comme le reste du formulaire). */
    public function removeImage(): void
    {
        $this->image = null;
        $this->image_path = null;
    }

    public function save(): void
    {
        $data = $this->validate();
        unset($data['image']);

        if ($this->image instanceof UploadedFile) {
            $data['image_path'] = $this->image->store('events', 'public');
        } else {
            $data['image_path'] = $this->image_path;
        }

        if ($this->editingId) {
            $event = Event::findOrFail($this->editingId);
            if ($event->image_path && $event->image_path !== $data['image_path']) {
                Storage::disk('public')->delete($event->image_path);
            }
            $event->update($data);
            $message = 'Événement mis à jour.';
        } else {
            $data['position'] = (int) Event::max('position') + 1;
            Event::create($data);
            $message = 'Événement créé.';
        }

        $this->showForm = false;
        $this->dispatch('notify', message: $message);
    }

    public function delete(Event $event): void
    {
        if ($event->image_path) {
            Storage::disk('public')->delete($event->image_path);
        }
        $event->delete();
        $this->dispatch('notify', message: 'Événement supprimé.');
    }

    public function render()
    {
        return view('livewire.admin.events', [
            'events' => Event::orderByRaw('starts_at is null')->orderBy('starts_at')->orderBy('position')->get(),
            'statuses' => EventStatus::cases(),
        ]);
    }
}

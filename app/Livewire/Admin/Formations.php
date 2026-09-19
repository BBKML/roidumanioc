<?php

namespace App\Livewire\Admin;

use App\Enums\FormationStatus;
use App\Models\Formation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.admin')]
class Formations extends Component
{
    use WithFileUploads;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $title = '';

    public string $category = '';

    public ?string $description = null;

    public int $price = 0;

    public string $status = 'brouillon';

    /** Image en attente de téléversement (formulaire ouvert). */
    public $image = null;

    /** Chemin de l'image déjà enregistrée (aperçu + gardée si aucune nouvelle n'est choisie). */
    public ?string $image_path = null;

    public ?string $duration_label = null;

    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'category' => ['required', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:brouillon,publiee'],
            'image' => ['nullable', 'image', 'max:4096'],
            'duration_label' => ['nullable', 'string', 'max:40'],
        ];
    }

    public function new(): void
    {
        $this->reset('editingId', 'title', 'category', 'description', 'image', 'image_path', 'duration_label');
        $this->price = 0;
        $this->status = 'brouillon';
        $this->resetValidation();
        $this->showForm = true;
    }

    public function edit(int $formationId): void
    {
        $formation = Formation::findOrFail($formationId);
        $this->editingId = $formation->id;
        $this->title = $formation->title;
        $this->category = (string) $formation->category;
        $this->description = $formation->description;
        $this->price = $formation->price;
        $this->status = $formation->status->value;
        $this->image = null;
        $this->image_path = $formation->image_path;
        $this->duration_label = $formation->duration_label;
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
            $data['image_path'] = $this->image->store('formations', 'public');
        } else {
            $data['image_path'] = $this->image_path;
        }

        if ($this->editingId) {
            $formation = Formation::findOrFail($this->editingId);
            if ($formation->image_path && $formation->image_path !== $data['image_path']) {
                Storage::disk('public')->delete($formation->image_path);
            }
            $formation->update($data);
            $message = 'Formation mise à jour.';
        } else {
            $data['position'] = (int) Formation::max('position') + 1;
            Formation::create($data);
            $message = 'Formation créée.';
        }

        $this->showForm = false;
        $this->dispatch('notify', message: $message);
    }

    public function togglePublish(int $formationId): void
    {
        $formation = Formation::findOrFail($formationId);
        $formation->update([
            'status' => $formation->status === FormationStatus::Publiee
                ? FormationStatus::Brouillon
                : FormationStatus::Publiee,
        ]);
    }

    public function delete(int $formationId): void
    {
        $formation = Formation::findOrFail($formationId);
        if ($formation->image_path) {
            Storage::disk('public')->delete($formation->image_path);
        }
        $formation->delete();
        $this->dispatch('notify', message: 'Formation supprimée.');
    }

    public function render()
    {
        return view('livewire.admin.formations', [
            'formations' => Formation::withCount([
                'lessons',
                'enrollments as validated_count' => fn ($q) => $q->where('status', 'validee'),
            ])->orderBy('position')->get(),
            'statuses' => FormationStatus::cases(),
        ]);
    }
}

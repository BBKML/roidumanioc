<?php

namespace App\Livewire\Admin;

use App\Models\Formation;
use App\Models\Lesson;
use App\Models\LessonAttachment;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.admin')]
class LessonManager extends Component
{
    use WithFileUploads;

    public Formation $formation;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $title = '';

    public string $type = 'video';

    public ?string $duration_label = null;

    public ?string $content = null;

    public string $videoProvider = 'none';   // none | link | bunny | upload

    public ?string $videoUrl = null;

    public $videoUpload = null;

    public bool $hasStoredVideo = false;

    // Ressources jointes (leçon en cours d'édition uniquement).
    public $attachmentFile = null;

    public string $attachmentTitle = '';

    public function mount(Formation $formation): void
    {
        $this->formation = $formation;
    }

    protected function rules(): array
    {
        $maxKb = config('media.video_max_mb') * 1024;

        return [
            'title' => ['required', 'string', 'max:160'],
            'type' => ['required', Rule::in(['video', 'document', 'quiz'])],
            'duration_label' => ['nullable', 'string', 'max:40'],
            'content' => ['nullable', 'string', 'max:20000'],
            'videoProvider' => ['required', 'in:none,link,bunny,upload'],
            'videoUrl' => [
                Rule::requiredIf(fn () => in_array($this->videoProvider, ['link', 'bunny'], true)),
                'nullable', 'string', 'max:500',
            ],
            'videoUpload' => [
                Rule::requiredIf(fn () => $this->videoProvider === 'upload' && ! $this->hasStoredVideo),
                'nullable', 'file', 'mimetypes:'.implode(',', config('media.video_mimetypes')), "max:$maxKb",
            ],
        ];
    }

    public function new(): void
    {
        $this->reset('editingId', 'title', 'duration_label', 'content', 'videoUrl', 'videoUpload');
        $this->type = 'video';
        $this->videoProvider = 'none';
        $this->hasStoredVideo = false;
        $this->resetValidation();
        $this->showForm = true;
    }

    public function edit(Lesson $lesson): void
    {
        abort_unless($lesson->formation_id === $this->formation->id, 404);

        $this->editingId = $lesson->id;
        $this->title = $lesson->title;
        $this->type = $lesson->type->value;
        $this->duration_label = $lesson->duration_label;
        $this->content = $lesson->content;
        $this->videoProvider = $lesson->video_provider ?: 'none';
        $this->videoUrl = $lesson->video_url;
        $this->videoUpload = null;
        $this->hasStoredVideo = filled($lesson->video_path);
        $this->reset('attachmentFile', 'attachmentTitle');
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate();

        $isNew = ! $this->editingId;
        $data = [
            'title' => $this->title,
            'type' => $this->type,
            'duration_label' => $this->duration_label,
            'content' => $this->content,
            'video_provider' => $this->videoProvider,
        ];

        $lesson = $this->editingId
            ? $this->formation->lessons()->whereKey($this->editingId)->firstOrFail()
            : $this->formation->lessons()->make(['position' => (int) $this->formation->lessons()->max('position') + 1]);

        // --- Vidéo selon la source ---
        if (in_array($this->videoProvider, ['link', 'bunny'], true)) {
            $data['video_url'] = trim($this->videoUrl);
            $data['video_path'] = null;
            $data['video_disk'] = null;
            $this->deleteStoredVideo($lesson);
        } elseif ($this->videoProvider === 'upload') {
            $data['video_url'] = null;
            if ($this->videoUpload) {
                $this->deleteStoredVideo($lesson);
                $disk = config('media.video_disk');
                $data['video_disk'] = $disk;
                $data['video_path'] = $this->videoUpload->store('lesson-videos/'.now()->format('Y/m'), $disk);
            }
        } else { // none
            $data['video_url'] = null;
            $data['video_path'] = null;
            $data['video_disk'] = null;
            $this->deleteStoredVideo($lesson);
        }

        $lesson->fill($data)->save();

        $this->editingId = $lesson->id;
        $this->hasStoredVideo = filled($lesson->video_path);
        $this->videoUpload = null;

        $this->dispatch('notify', message: $isNew ? 'Leçon ajoutée.' : 'Leçon enregistrée.');

        // Leçon « document » : on garde la modale ouverte pour joindre le PDF.
        if (! ($isNew && $this->type === 'document')) {
            $this->showForm = false;
        }
    }

    private function deleteStoredVideo(Lesson $lesson): void
    {
        if (filled($lesson->video_path) && $lesson->video_disk) {
            Storage::disk($lesson->video_disk)->delete($lesson->video_path);
        }
    }

    public function addAttachment(): void
    {
        abort_unless($this->editingId, 403);

        // Les ressources sont facultatives : sans fichier, on ne fait rien.
        if (blank($this->attachmentFile)) {
            $this->dispatch('notify', message: 'Choisissez un fichier à joindre.');

            return;
        }

        $maxKb = config('media.attachment_max_mb') * 1024;
        $this->validate([
            'attachmentTitle' => ['nullable', 'string', 'max:160'],
            'attachmentFile' => ['file', 'mimetypes:'.implode(',', config('media.attachment_mimetypes')), "max:$maxKb"],
        ]);

        $lesson = $this->formation->lessons()->whereKey($this->editingId)->firstOrFail();
        $disk = config('media.attachment_disk');
        $title = trim((string) $this->attachmentTitle)
            ?: pathinfo($this->attachmentFile->getClientOriginalName(), PATHINFO_FILENAME);

        $lesson->attachments()->create([
            'title' => Str::limit($title, 155, ''),
            'disk' => $disk,
            'path' => $this->attachmentFile->store('lesson-files/'.now()->format('Y/m'), $disk),
            'mime' => $this->attachmentFile->getMimeType(),
            'size' => $this->attachmentFile->getSize(),
            'position' => (int) $lesson->attachments()->max('position') + 1,
        ]);

        $this->reset('attachmentFile', 'attachmentTitle');
        $this->dispatch('notify', message: 'Ressource ajoutée.');
    }

    public function removeAttachment(LessonAttachment $attachment): void
    {
        abort_unless($attachment->lesson->formation_id === $this->formation->id, 404);
        $attachment->delete();
        $this->dispatch('notify', message: 'Ressource supprimée.');
    }

    public function delete(Lesson $lesson): void
    {
        abort_unless($lesson->formation_id === $this->formation->id, 404);
        $this->deleteStoredVideo($lesson);
        $lesson->attachments->each->delete();
        $lesson->delete();
        $this->dispatch('notify', message: 'Leçon supprimée.');
    }

    public function move(Lesson $lesson, string $direction): void
    {
        abort_unless($lesson->formation_id === $this->formation->id, 404);

        $neighbour = $this->formation->lessons()
            ->when($direction === 'up',
                fn ($q) => $q->where('position', '<', $lesson->position)->orderByDesc('position'),
                fn ($q) => $q->where('position', '>', $lesson->position)->orderBy('position'),
            )->first();

        if (! $neighbour) {
            return;
        }

        [$lesson->position, $neighbour->position] = [$neighbour->position, $lesson->position];
        $lesson->save();
        $neighbour->save();
    }

    public function render()
    {
        return view('livewire.admin.lesson-manager', [
            'lessons' => $this->formation->lessons()->withCount('attachments')->orderBy('position')->get(),
            'editingAttachments' => $this->editingId
                ? LessonAttachment::where('lesson_id', $this->editingId)->orderBy('position')->get()
                : collect(),
            'videoMaxMb' => config('media.video_max_mb'),
            'attachmentMaxMb' => config('media.attachment_max_mb'),
        ]);
    }
}

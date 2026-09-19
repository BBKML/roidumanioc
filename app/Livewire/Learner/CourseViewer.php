<?php

namespace App\Livewire\Learner;

use App\Enums\EnrollmentStatus;
use App\Models\Formation;
use App\Models\Lesson;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.learner')]
class CourseViewer extends Component
{
    use AuthorizesRequests;

    public Formation $formation;

    public ?int $currentLessonId = null;

    public function mount(Formation $formation): void
    {
        $this->authorize('follow', $formation);

        $this->formation = $formation->load('lessons.attachments');

        // Une formation gratuite consultée directement crée l'inscription (suivi + tableau de bord).
        if ($formation->isFree()) {
            $formation->enrollments()->firstOrCreate(
                ['user_id' => auth()->id()],
                ['status' => EnrollmentStatus::Validee, 'enrolled_at' => now()],
            );
        }

        $done = $this->completedIds();
        $this->currentLessonId = $this->formation->lessons
            ->first(fn (Lesson $l) => ! $done->contains($l->id))?->id
            ?? $this->formation->lessons->first()?->id;
    }

    private function completedIds()
    {
        return auth()->user()->lessonProgress()
            ->whereIn('lesson_id', $this->formation->lessons->pluck('id'))
            ->pluck('lesson_id');
    }

    public function select(int $lessonId): void
    {
        if ($this->formation->lessons->contains('id', $lessonId)) {
            $this->currentLessonId = $lessonId;
        }
    }

    public function go(string $direction): void
    {
        $ids = $this->formation->lessons->pluck('id')->values();
        $i = $ids->search($this->currentLessonId);

        if ($direction === 'prev' && $i > 0) {
            $this->currentLessonId = $ids[$i - 1];
        }
        if ($direction === 'next' && $i !== false && $i < $ids->count() - 1) {
            $this->currentLessonId = $ids[$i + 1];
        }
    }

    public function toggleComplete(): void
    {
        $user = auth()->user();
        $existing = $user->lessonProgress()->where('lesson_id', $this->currentLessonId)->first();

        if ($existing) {
            $existing->delete();
        } else {
            $user->lessonProgress()->create([
                'lesson_id' => $this->currentLessonId,
                'completed_at' => now(),
            ]);
        }
    }

    public function render()
    {
        $lessons = $this->formation->lessons;
        $done = $this->completedIds();
        $current = $lessons->firstWhere('id', $this->currentLessonId) ?? $lessons->first();

        return view('livewire.learner.course-viewer', [
            'lessons' => $lessons,
            'current' => $current,
            'currentIndex' => $current ? $lessons->search(fn ($l) => $l->id === $current->id) : 0,
            'doneIds' => $done,
            'progress' => $this->formation->progressFor(auth()->user()),
        ]);
    }
}

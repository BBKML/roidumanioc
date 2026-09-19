<?php

namespace App\Livewire\Learner;

use App\Enums\EnrollmentStatus;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.learner')]
class Progress extends Component
{
    public function render()
    {
        $user = auth()->user();

        $rows = $user->enrollments()
            ->where('status', EnrollmentStatus::Validee)
            ->with('formation.lessons')
            ->get()
            ->map(fn ($enrollment) => [
                'formation' => $enrollment->formation,
                'progress' => $enrollment->formation->progressFor($user),
            ]);

        return view('livewire.learner.progress', ['rows' => $rows]);
    }
}

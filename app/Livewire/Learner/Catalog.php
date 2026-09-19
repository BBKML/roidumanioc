<?php

namespace App\Livewire\Learner;

use App\Enums\EnrollmentStatus;
use App\Models\Formation;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.learner')]
class Catalog extends Component
{
    use AuthorizesRequests;

    public function enrollFree(int $formationId)
    {
        $formation = Formation::findOrFail($formationId);
        $this->authorize('enroll', $formation);
        abort_unless($formation->isFree(), 403);

        $formation->enrollments()->updateOrCreate(
            ['user_id' => auth()->id()],
            ['status' => EnrollmentStatus::Validee, 'enrolled_at' => now()],
        );

        session()->flash('flash', 'Bienvenue dans « '.$formation->title.' ». Bon apprentissage !');

        return $this->redirectRoute('learner.course', $formation, navigate: true);
    }

    public function buy(int $formationId)
    {
        $formation = Formation::findOrFail($formationId);
        $this->authorize('enroll', $formation);

        return $this->redirectRoute('learner.checkout', $formation, navigate: true);
    }

    public function render()
    {
        $user = auth()->user();

        $formations = Formation::published()
            ->withCount('lessons')
            ->orderBy('position')
            ->get()
            ->map(function (Formation $f) use ($user) {
                $f->setAttribute('is_enrolled', $user->isEnrolledIn($f));
                $f->setAttribute('is_pending', $user->hasPendingPaymentFor($f));

                return $f;
            });

        return view('livewire.learner.catalog', ['formations' => $formations]);
    }
}

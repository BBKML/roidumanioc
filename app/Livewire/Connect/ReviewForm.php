<?php

namespace App\Livewire\Connect;

use App\Actions\SubmitReview;
use App\Enums\ReviewDirection;
use App\Models\Collaboration;
use App\Models\Review;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

/**
 * Formulaire de notation en fin de collaboration — intégré sous les 3 blocs de
 * Collaboration\Show, une fois `terminee`. Immuable après soumission : pas de méthode
 * d'édition exposée ici ni ailleurs.
 */
class ReviewForm extends Component
{
    public Collaboration $collaboration;

    public int $rating = 5;

    /** @var array<string, int> */
    public array $criteria = [];

    public string $comment = '';

    public function mount(Collaboration $collaboration): void
    {
        $this->collaboration = $collaboration;

        foreach (array_keys($this->direction()->criteria()) as $key) {
            $this->criteria[$key] = 5;
        }
    }

    private function direction(): ReviewDirection
    {
        return $this->collaboration->isProducer(Auth::user())
            ? ReviewDirection::ProducteurVersAcheteur
            : ReviewDirection::AcheteurVersProducteur;
    }

    public function submit(): void
    {
        $this->authorize('create', [Review::class, $this->collaboration]);

        $this->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'criteria.*' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            app(SubmitReview::class)->handle(
                Auth::user(), $this->collaboration, $this->rating, $this->criteria, $this->comment !== '' ? $this->comment : null,
            );
        } catch (ValidationException $e) {
            $this->addError('rating', $e->validator->errors()->first());

            return;
        }

        $this->dispatch('notify', message: 'Merci pour votre évaluation !');
    }

    public function render()
    {
        $user = Auth::user();
        $direction = $this->direction();

        return view('livewire.connect.review-form', [
            'canReview' => $this->collaboration->canBeReviewedBy($user),
            'myReview' => $this->collaboration->reviews()->where('rater_id', $user->id)->first(),
            'theirReview' => $this->collaboration->reviews()->where('rater_id', '!=', $user->id)->first(),
            'criteriaLabels' => $direction->criteria(),
        ]);
    }
}

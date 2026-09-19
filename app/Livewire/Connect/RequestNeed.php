<?php

namespace App\Livewire\Connect;

use App\Actions\CreateConnectionRequest;
use App\Models\BuyerNeed;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Un producteur amorce une mise en relation à propos d'un besoin acheteur.
 */
#[Layout('components.layouts.learner')]
class RequestNeed extends Component
{
    public BuyerNeed $need;

    public string $message = '';

    // Nullable (pas string) : Livewire convertit automatiquement un champ vidé en `null`
    // pour une propriété typée ainsi, ce qui permet à `nullable` de fonctionner correctement
    // en validation (même convention que Producer\OfferForm::$price_indicative).
    public ?float $quantity = null;

    public ?int $priceTotal = null;

    public function mount(BuyerNeed $need): void
    {
        abort_unless(BuyerNeed::open()->whereKey($need->id)->exists(), 404);
        abort_if($need->buyerProfile->user_id === Auth::id(), 403, 'Vous ne pouvez pas répondre à votre propre besoin.');

        $this->need = $need;
        // Pré-rempli avec les valeurs du besoin — le producteur les ajuste s'il négocie, ou
        // vide les champs pour simplement discuter d'abord sans proposer tout de suite.
        $this->quantity = (float) $need->quantity;
        $this->priceTotal = $need->budget_indicative;
    }

    public function send()
    {
        $validated = $this->validate([
            'message' => ['nullable', 'string', 'max:1000'],
            'quantity' => ['nullable', 'numeric', 'min:0.01', 'max:99999999.99', 'required_with:priceTotal'],
            'priceTotal' => ['nullable', 'integer', 'min:1'],
        ]);

        try {
            $connectionRequest = app(CreateConnectionRequest::class)->handle(
                Auth::user(),
                'producteur',
                $this->need,
                $this->message !== '' ? $this->message : null,
                $validated['quantity'],
                $validated['priceTotal'],
            );
        } catch (ValidationException $e) {
            // Erreur générale de l'action (doublon, rate limit) — jamais rattachée au champ
            // "message" du formulaire, pour ne pas laisser croire que le texte tapé est en cause.
            $this->addError('general', $e->validator->errors()->first());

            return null;
        }

        $this->dispatch('notify', message: 'Réponse envoyée.');

        return redirect()->route('learner.requests.show', $connectionRequest);
    }

    public function render()
    {
        return view('livewire.connect.request-need');
    }
}

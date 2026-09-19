<?php

namespace App\Livewire\Admin;

use App\Enums\RegistrationFormStatus;
use App\Models\RegistrationForm;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class RegistrationForms extends Component
{
    public function togglePublish(int $id): void
    {
        $form = RegistrationForm::findOrFail($id);
        $form->update([
            'status' => $form->status === RegistrationFormStatus::Publiee
                ? RegistrationFormStatus::Brouillon
                : RegistrationFormStatus::Publiee,
        ]);
    }

    public function delete(int $id): void
    {
        $form = RegistrationForm::withCount('leads')->findOrFail($id);

        if ($form->leads_count > 0) {
            $this->dispatch('notify', message: 'Impossible de supprimer : ce formulaire a déjà des prospects. Dépubliez-le plutôt.');

            return;
        }

        $form->delete();
        $this->dispatch('notify', message: 'Formulaire supprimé.');
    }

    public function render()
    {
        return view('livewire.admin.registration-forms', [
            'forms' => RegistrationForm::withCount('leads')->latest()->get(),
        ]);
    }
}

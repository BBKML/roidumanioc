<?php

namespace App\Livewire\Admin;

use App\Actions\ImportRegistrationLeads;
use App\Models\RegistrationForm;
use App\Models\RegistrationLead;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

/**
 * Liste en cartes (pas un <table>), choix délibéré : chaque prospect porte ~13 champs
 * dépliables (profession, motivations, moyen de paiement…) — du contenu trop riche pour
 * des colonnes. Même raisonnement que Payments/Messages/CommunityModeration (audit
 * architecture) : les écrans "fiche à consulter" restent en cartes, les écrans "liste de
 * champs comparables" restent en <table>.
 */
#[Layout('components.layouts.admin')]
class RegistrationLeads extends Component
{
    use WithFileUploads, WithPagination;

    public string $search = '';

    public string $formId = '';

    public string $filter = 'nouveau';

    public ?int $openId = null;

    public string $note = '';

    public bool $showImport = false;

    public ?int $importFormId = null;

    public $importFile = null;

    /** @var array{created:int,duplicates:int,errors:array<int,string>}|null */
    public ?array $importResult = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFormId(): void
    {
        $this->resetPage();
    }

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
        $this->resetPage();
    }

    public function open(int $id): void
    {
        $this->openId = $this->openId === $id ? null : $id;
        $this->note = $this->openId ? (RegistrationLead::find($id)->admin_note ?? '') : '';
    }

    public function saveNote(int $id): void
    {
        RegistrationLead::findOrFail($id)->update(['admin_note' => $this->note]);
        $this->dispatch('notify', message: 'Note enregistrée.');
    }

    public function markContacted(int $id): void
    {
        RegistrationLead::findOrFail($id)->markContacted(Auth::user());
    }

    public function markEnrolled(int $id): void
    {
        RegistrationLead::findOrFail($id)->markEnrolled(Auth::user());
    }

    public function markAbandoned(int $id): void
    {
        RegistrationLead::findOrFail($id)->markAbandoned(Auth::user());
    }

    public function reopen(int $id): void
    {
        RegistrationLead::findOrFail($id)->reopen();
    }

    public function delete(int $id): void
    {
        RegistrationLead::findOrFail($id)->delete();
        $this->dispatch('notify', message: 'Prospect supprimé.');
    }

    public function openImport(): void
    {
        $this->importFormId = $this->formId !== '' ? (int) $this->formId : null;
        $this->importFile = null;
        $this->importResult = null;
        $this->resetValidation();
        $this->showImport = true;
    }

    public function import(ImportRegistrationLeads $importer): void
    {
        $this->validate([
            'importFormId' => ['required', 'integer', 'exists:registration_forms,id'],
            'importFile' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ], [], ['importFormId' => 'campagne', 'importFile' => 'fichier']);

        $form = RegistrationForm::findOrFail($this->importFormId);

        $this->importResult = $importer->handle($form, $this->importFile);
        $this->importFile = null;

        $this->dispatch('notify', message: "{$this->importResult['created']} prospect(s) importé(s).");
    }

    private function counts(): array
    {
        return [
            'nouveau' => RegistrationLead::where('status', 'nouveau')->count(),
            'contacte' => RegistrationLead::where('status', 'contacte')->count(),
            'inscrit' => RegistrationLead::where('status', 'inscrit')->count(),
            'abandonne' => RegistrationLead::where('status', 'abandonne')->count(),
        ];
    }

    public function render()
    {
        $query = RegistrationLead::with('registrationForm')->latest();

        if ($this->filter !== 'tous') {
            $query->where('status', $this->filter);
        }

        if ($this->formId !== '') {
            $query->where('registration_form_id', $this->formId);
        }

        if ($this->search !== '') {
            $query->where(fn ($q) => $q
                ->where('first_name', 'like', "%{$this->search}%")
                ->orWhere('last_name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")
                ->orWhere('phone_1', 'like', "%{$this->search}%"));
        }

        return view('livewire.admin.registration-leads', [
            'leads' => $query->paginate(15),
            'forms' => RegistrationForm::orderBy('title')->get(),
            'counts' => $this->counts(),
        ]);
    }
}

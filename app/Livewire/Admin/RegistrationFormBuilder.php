<?php

namespace App\Livewire\Admin;

use App\Models\RegistrationForm;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Page dédiée de création/édition d'un formulaire d'inscription — pensée comme un éditeur
 * Google Forms : un seul écran, du titre jusqu'au tarif, avec un aperçu qui se met à jour en
 * direct. Remplace l'ancienne fenêtre modale (RegistrationForms ne gère plus que la liste).
 */
#[Layout('components.layouts.admin')]
class RegistrationFormBuilder extends Component
{
    use WithFileUploads;

    public ?int $editingId = null;

    public bool $isNew = true;

    public bool $showPreview = true;

    public string $previewWidth = 'mobile';

    public bool $justSaved = false;

    public string $title = '';

    public string $slug = '';

    public bool $slugTouched = false;

    public string $status = 'brouillon';

    public ?string $subtitle = null;

    public $coverImage = null;

    public ?string $cover_image_path = null;

    public ?string $originalCoverImagePath = null;

    public ?string $whatsapp_number = null;

    public ?string $intro = null;

    public ?string $objectives = null;

    public ?string $schedule_info = null;

    public ?string $program = null;

    public ?string $certifications = null;

    public ?string $price_amount = null;

    public ?string $price_note = null;

    public ?string $payment_methods = null;

    /** Format `datetime-local` (ex : 2026-10-01T23:59). Convertie en Carbon à l'enregistrement. */
    public ?string $early_bird_deadline = null;

    public ?string $deposit_amount = null;

    public ?string $deposit_note = null;

    /** État d'édition « façon Google Forms » des champs à points : liste d'items
     *  ['type' => 'text'|'heading', 'label' => string, 'text' => string], reconvertis en texte
     *  brut (même format que RegistrationForm::renderLines()) à chaque modification — l'admin
     *  n'écrit jamais de **gras** ou de ## à la main. */
    public array $items = [];

    private const ITEM_FIELDS = [
        'objectives', 'schedule_info', 'program', 'certifications', 'payment_methods', 'price_note',
    ];

    public function mount(?RegistrationForm $form = null): void
    {
        foreach (self::ITEM_FIELDS as $field) {
            $this->items[$field] = [];
        }

        if (! $form || ! $form->exists) {
            return;
        }

        $this->isNew = false;
        $this->editingId = $form->id;
        $this->title = $form->title;
        $this->slug = $form->slug;
        $this->slugTouched = true;
        $this->status = $form->status->value;
        $this->subtitle = $form->subtitle;
        $this->cover_image_path = $form->cover_image_path;
        $this->originalCoverImagePath = $form->cover_image_path;
        $this->whatsapp_number = $form->whatsapp_number;
        $this->intro = $form->intro;
        $this->objectives = $form->objectives;
        $this->schedule_info = $form->schedule_info;
        $this->program = $form->program;
        $this->certifications = $form->certifications;
        $this->price_amount = $form->price_amount;
        $this->price_note = $form->price_note;
        $this->payment_methods = $form->payment_methods;
        $this->early_bird_deadline = $form->early_bird_deadline?->format('Y-m-d\TH:i');
        $this->deposit_amount = $form->deposit_amount;
        $this->deposit_note = $form->deposit_note;

        foreach (self::ITEM_FIELDS as $field) {
            $this->items[$field] = $this->parseItemsFromText($this->{$field});
        }
    }

    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'slug' => [
                'required', 'string', 'max:160', 'alpha_dash',
                Rule::unique('registration_forms', 'slug')->ignore($this->editingId),
            ],
            'status' => ['required', 'in:brouillon,publiee'],
            'subtitle' => ['nullable', 'string', 'max:200'],
            'coverImage' => ['nullable', 'image', 'max:4096'],
            'whatsapp_number' => ['nullable', 'string', 'max:40'],
            'intro' => ['nullable', 'string', 'max:2000'],
            'objectives' => ['nullable', 'string', 'max:3000'],
            'schedule_info' => ['nullable', 'string', 'max:3000'],
            'program' => ['nullable', 'string', 'max:3000'],
            'certifications' => ['nullable', 'string', 'max:3000'],
            'price_amount' => ['nullable', 'string', 'max:120'],
            'price_note' => ['nullable', 'string', 'max:2000'],
            'payment_methods' => ['nullable', 'string', 'max:2000'],
            'early_bird_deadline' => ['nullable', 'date'],
            'deposit_amount' => ['nullable', 'string', 'max:120'],
            'deposit_note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'title' => 'titre',
            'slug' => 'lien public',
            'status' => 'statut',
            'subtitle' => 'sous-titre',
            'coverImage' => 'affiche',
            'whatsapp_number' => 'numéro WhatsApp',
            'price_amount' => 'tarif affiché',
            'early_bird_deadline' => 'échéance premiers inscrits',
            'deposit_amount' => 'montant de la réservation',
        ];
    }

    /** Suggère le slug depuis le titre tant que l'admin n'a pas modifié le slug lui-même. */
    public function updatedTitle(string $value): void
    {
        if (! $this->slugTouched) {
            $this->slug = Str::slug($value);
        }
    }

    public function updatedSlug(): void
    {
        $this->slugTouched = true;
    }

    /* ---------------- Éditeur de points (façon Google Forms) ---------------- */

    public function addItem(string $field, string $type = 'text'): void
    {
        $this->items[$field][] = ['type' => $type, 'label' => '', 'text' => ''];
        $this->syncField($field);
    }

    public function removeItem(string $field, int $index): void
    {
        unset($this->items[$field][$index]);
        $this->items[$field] = array_values($this->items[$field]);
        $this->syncField($field);
    }

    public function moveItem(string $field, int $index, int $direction): void
    {
        $target = $index + $direction;
        if (! isset($this->items[$field][$index], $this->items[$field][$target])) {
            return;
        }
        [$this->items[$field][$index], $this->items[$field][$target]]
            = [$this->items[$field][$target], $this->items[$field][$index]];
        $this->syncField($field);
    }

    /** Livewire appelle ce hook à chaque modification d'une propriété liée par wire:model —
     *  on en profite pour recomposer le champ texte réel dès qu'un point est édité. */
    public function updated($name): void
    {
        if (preg_match('/^items\.([a-z_]+)\./', $name, $m) && in_array($m[1], self::ITEM_FIELDS, true)) {
            $this->syncField($m[1]);
        }
    }

    private function syncField(string $field): void
    {
        $this->{$field} = $this->serializeItems($this->items[$field] ?? []);
    }

    /** Reconstitue les points éditables à partir du texte stocké — même convention que
     *  RegistrationForm::lines()/renderLines() (## = sous-titre, **texte** = partie en gras). */
    private function parseItemsFromText(?string $text): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $text))
            ->map(fn ($line) => trim($line))
            ->filter(fn ($line) => $line !== '')
            ->map(function (string $line) {
                if (str_starts_with($line, '##')) {
                    return ['type' => 'heading', 'label' => '', 'text' => ltrim(substr($line, 2))];
                }
                if (preg_match('/^\*\*(.+?)\*\*\s*(.*)$/s', $line, $m)) {
                    return ['type' => 'text', 'label' => $m[1], 'text' => $m[2]];
                }

                return ['type' => 'text', 'label' => '', 'text' => $line];
            })
            ->values()
            ->all();
    }

    private function serializeItems(array $items): string
    {
        return collect($items)
            ->map(function (array $item) {
                $text = trim($item['text'] ?? '');
                if (($item['type'] ?? 'text') === 'heading') {
                    return $text !== '' ? '## '.$text : null;
                }
                $label = trim($item['label'] ?? '');
                if ($label === '') {
                    return $text !== '' ? $text : null;
                }
                if ($text === '') {
                    return "**{$label}**";
                }
                // Pas d'espace avant une virgule/point qui doit rester collé au gras (ex : "**Titre**, suite").
                // Le français met en revanche une espace avant : ; ! ? — on la garde (comportement par défaut).
                $glue = preg_match('/^[,.)]/u', $text) ? '' : ' ';

                return "**{$label}**{$glue}{$text}";
            })
            ->filter()
            ->implode("\n");
    }

    /** Aperçu en direct : réutilise le vrai template public (mêmes styles, mêmes règles de
     *  mise en forme) avec un formulaire non enregistré, construit depuis l'état courant. */
    public function previewHtml(): string
    {
        $draft = new RegistrationForm([
            'title' => $this->title !== '' ? $this->title : 'Titre de la formation',
            'slug' => $this->slug !== '' ? $this->slug : 'apercu',
            'subtitle' => $this->subtitle,
            'cover_image_path' => $this->cover_image_path,
            'whatsapp_number' => $this->whatsapp_number,
            'intro' => $this->intro,
            'objectives' => $this->objectives,
            'schedule_info' => $this->schedule_info,
            'program' => $this->program,
            'certifications' => $this->certifications,
            'price_amount' => $this->price_amount,
            'price_note' => $this->price_note,
            'payment_methods' => $this->payment_methods,
            'early_bird_deadline' => blank($this->early_bird_deadline) ? null : $this->early_bird_deadline,
            'deposit_amount' => $this->deposit_amount,
            'deposit_note' => $this->deposit_note,
        ]);

        return view('public.registration-form', [
            'form' => $draft,
            'preview' => true,
            'errors' => session()->get('errors') ?: new ViewErrorBag,
        ])->render();
    }

    public function coverImagePreviewUrl(): ?string
    {
        return ($this->cover_image_path && $this->slug) ? route('inscription.image', $this->slug) : null;
    }

    /** Retire l'affiche actuelle (staged — effectif à l'enregistrement). */
    public function removeCoverImage(): void
    {
        $this->coverImage = null;
        $this->cover_image_path = null;
    }

    public function togglePreview(): void
    {
        $this->showPreview = ! $this->showPreview;
    }

    public function save()
    {
        $this->early_bird_deadline = blank($this->early_bird_deadline) ? null : $this->early_bird_deadline;

        $data = $this->validate();
        unset($data['coverImage']);

        if ($this->coverImage) {
            if ($this->originalCoverImagePath) {
                Storage::disk('public')->delete($this->originalCoverImagePath);
            }
            $data['cover_image_path'] = $this->coverImage->store('registration-forms', 'public');
            $this->coverImage = null;
        } elseif ($this->originalCoverImagePath && $this->cover_image_path === null) {
            Storage::disk('public')->delete($this->originalCoverImagePath);
            $data['cover_image_path'] = null;
        } else {
            $data['cover_image_path'] = $this->cover_image_path;
        }

        if ($this->editingId) {
            RegistrationForm::findOrFail($this->editingId)->update($data);
            $this->cover_image_path = $data['cover_image_path'];
            $this->originalCoverImagePath = $data['cover_image_path'];
            $this->justSaved = true;
            $this->dispatch('notify', message: 'Formulaire mis à jour.');

            return null;
        }

        $form = RegistrationForm::create($data);
        $this->dispatch('notify', message: 'Formulaire créé.');

        return redirect()->route('admin.registration-forms.edit', $form->slug);
    }

    public function render()
    {
        return view('livewire.admin.registration-form-builder');
    }
}

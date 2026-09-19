<?php

namespace App\Livewire\Admin;

use App\Models\Award;
use App\Models\Partner;
use App\Models\SiteContent;
use App\Models\Testimonial;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.admin')]
class ContentManager extends Component
{
    use WithFileUploads;

    /** Contenu de toutes les sections, indexé par clé. */
    public array $data = [];

    /** Fichiers image en attente d'enregistrement, indexés comme $data (ex: imageFiles['hero']['image']). */
    public array $imageFiles = [];

    /** Témoignages & distinctions (modèles dédiés). */
    public array $testimonials = [];

    public array $awards = [];

    /** Partenaires (logos défilants, section Communauté — modèle dédié). */
    public array $partners = [];

    /** Logo en attente d'enregistrement, indexé comme $partners (partnerLogos[$i]). */
    public array $partnerLogos = [];

    #[Url]
    public ?string $open = null;

    /** Champs "liste" (un élément par ligne dans un textarea). */
    private const LIST_PATHS = [
        'entete' => ['menu'],
        'hero' => ['trust'],
        'bandeau' => ['items'],
        'placali' => ['atouts'],
        'producteurs_section' => ['opportunities'],
    ];

    public function mount(): void
    {
        $this->data = SiteContent::query()->orderBy('position')->get()
            ->mapWithKeys(fn (SiteContent $s) => [$s->key => $s->data])
            ->all();

        // Listes bilingues : array -> texte multi-ligne pour l'édition (par langue).
        foreach (self::LIST_PATHS as $key => $paths) {
            foreach ($paths as $path) {
                foreach (config('locales.supported') as $locale) {
                    $arr = Arr::get($this->data, "$key.$path.$locale", []);
                    Arr::set($this->data, "$key.$path.$locale", implode("\n", (array) $arr));
                }
            }
        }

        $this->loadTestimonials();
        $this->loadAwards();
        $this->loadPartners();
    }

    private function loadTestimonials(): void
    {
        $this->testimonials = Testimonial::orderBy('position')->get()
            ->map(fn (Testimonial $t) => $t->only('id', 'quote', 'quote_en', 'author_name', 'author_role', 'author_role_en'))
            ->all();
    }

    private function loadAwards(): void
    {
        $this->awards = Award::orderBy('position')->get()
            ->map(fn (Award $a) => $a->only('id', 'year', 'title', 'title_en', 'description', 'description_en'))
            ->all();
    }

    private function loadPartners(): void
    {
        $this->partners = Partner::orderBy('position')->get()
            ->map(fn (Partner $p) => $p->only('id', 'name', 'logo_path'))
            ->all();
    }

    public function sections(): array
    {
        static $schema;

        return $schema ??= require app_path('Livewire/Admin/content-sections.php');
    }

    public function toggle(string $key): void
    {
        $this->open = $this->open === $key ? null : $key;
    }

    public function save(string $key): void
    {
        $section = $this->sections()[$key] ?? abort(404);

        $payload = $this->data[$key] ?? [];

        // Listes bilingues : texte multi-ligne -> array nettoyé (par langue).
        foreach (self::LIST_PATHS[$key] ?? [] as $path) {
            foreach (config('locales.supported') as $locale) {
                $lines = collect(preg_split('/\r\n|\r|\n/', (string) Arr::get($payload, "$path.$locale")))
                    ->map(fn ($l) => trim($l))
                    ->filter()
                    ->values()
                    ->all();
                Arr::set($payload, "$path.$locale", $lines);
            }
        }

        $this->storeUploadedImages($key, $payload);

        $rules = [];
        foreach ($section['fields'] as [$name, $type]) {
            if ($type === 'image') {
                $rules["payload.$name"] = ['nullable', 'array'];

                continue;
            }

            $leafRule = match ($type) {
                'textarea' => ['nullable', 'string', 'max:6000'],
                'html' => ['nullable', 'string', 'max:600'],
                'list' => ['nullable', 'array'],
                default => ['nullable', 'string', 'max:400'],
            };

            foreach (config('locales.supported') as $locale) {
                $rules["payload.$name.$locale"] = $leafRule;
            }
        }
        Validator::make(['payload' => $payload], $rules)->validate();

        SiteContent::where('key', $key)->firstOrFail()->update(['data' => $payload]);

        // On garde la version "texte" des listes dans l'état du composant.
        foreach (self::LIST_PATHS[$key] ?? [] as $path) {
            foreach (config('locales.supported') as $locale) {
                Arr::set($payload, "$path.$locale", implode("\n", (array) Arr::get($payload, "$path.$locale")));
            }
        }
        $this->data[$key] = $payload;

        $this->dispatch('notify', message: 'Section « '.$section['label'].' » enregistrée.');
    }

    /** Remplace les images téléversées dans $payload par leur chemin définitif sur le disque public. */
    private function storeUploadedImages(string $key, array &$payload): void
    {
        $files = Arr::get($this->imageFiles, $key, []);

        if (! $files) {
            return;
        }

        foreach (Arr::dot($files) as $path => $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            Validator::make(['image' => $file], ['image' => ['image', 'max:4096']])->validate();

            $oldSrc = Arr::get($payload, "$path.src");
            if (is_string($oldSrc) && str_starts_with($oldSrc, 'storage/')) {
                Storage::disk('public')->delete(Str::after($oldSrc, 'storage/'));
            }

            Arr::set($payload, "$path.src", 'storage/'.$file->store('site-content', 'public'));
        }

        Arr::set($this->imageFiles, $key, []);
    }

    /** Retire l'image d'un champ (effectif à l'enregistrement de la section). */
    public function removeImage(string $path): void
    {
        data_set($this, "$path.src", null);
        Arr::set($this->imageFiles, Str::after($path, 'data.'), null);
    }

    /* -------------------- Témoignages -------------------- */

    public function addTestimonial(): void
    {
        $t = Testimonial::create([
            'quote' => 'Nouveau témoignage à rédiger.',
            'author_name' => 'Prénom N.',
            'author_role' => 'Rôle · Ville',
            'position' => (int) Testimonial::max('position') + 1,
        ]);
        $this->loadTestimonials();
        $this->dispatch('notify', message: 'Témoignage ajouté.');
    }

    public function saveTestimonial(int $index): void
    {
        $row = $this->testimonials[$index] ?? abort(404);

        $data = Validator::make($row, [
            'quote' => ['required', 'string', 'max:600'],
            'quote_en' => ['nullable', 'string', 'max:600'],
            'author_name' => ['required', 'string', 'max:120'],
            'author_role' => ['nullable', 'string', 'max:120'],
            'author_role_en' => ['nullable', 'string', 'max:120'],
        ])->validate();

        Testimonial::whereKey($row['id'])->update($data);
        $this->dispatch('notify', message: 'Témoignage enregistré.');
    }

    public function deleteTestimonial(int $id): void
    {
        Testimonial::whereKey($id)->delete();
        $this->loadTestimonials();
        $this->dispatch('notify', message: 'Témoignage supprimé.');
    }

    /* -------------------- Distinctions -------------------- */

    public function addAward(): void
    {
        Award::create([
            'year' => (string) now()->year,
            'title' => 'Nouvelle distinction',
            'description' => 'Description à rédiger.',
            'position' => (int) Award::max('position') + 1,
        ]);
        $this->loadAwards();
        $this->dispatch('notify', message: 'Distinction ajoutée.');
    }

    public function saveAward(int $index): void
    {
        $row = $this->awards[$index] ?? abort(404);

        $data = Validator::make($row, [
            'year' => ['nullable', 'string', 'max:20'],
            'title' => ['required', 'string', 'max:160'],
            'title_en' => ['nullable', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:600'],
            'description_en' => ['nullable', 'string', 'max:600'],
        ])->validate();

        Award::whereKey($row['id'])->update($data);
        $this->dispatch('notify', message: 'Distinction enregistrée.');
    }

    public function deleteAward(int $id): void
    {
        Award::whereKey($id)->delete();
        $this->loadAwards();
        $this->dispatch('notify', message: 'Distinction supprimée.');
    }

    /* -------------------- Partenaires -------------------- */

    public function addPartner(): void
    {
        Partner::create([
            'name' => 'Nouveau partenaire',
            'position' => (int) Partner::max('position') + 1,
        ]);
        $this->loadPartners();
        $this->dispatch('notify', message: 'Partenaire ajouté.');
    }

    public function savePartner(int $index): void
    {
        $row = $this->partners[$index] ?? abort(404);

        $data = Validator::make($row, [
            'name' => ['required', 'string', 'max:120'],
        ])->validate();

        $logo = $this->partnerLogos[$index] ?? null;
        if ($logo instanceof UploadedFile) {
            Validator::make(['logo' => $logo], ['logo' => ['image', 'max:2048']])->validate();

            $partner = Partner::whereKey($row['id'])->firstOrFail();
            if ($partner->logo_path) {
                Storage::disk('public')->delete($partner->logo_path);
            }
            $data['logo_path'] = $logo->store('partners', 'public');
            unset($this->partnerLogos[$index]);
        }

        Partner::whereKey($row['id'])->update($data);
        $this->loadPartners();
        $this->dispatch('notify', message: 'Partenaire enregistré.');
    }

    public function deletePartner(int $id): void
    {
        $partner = Partner::find($id);
        if ($partner?->logo_path) {
            Storage::disk('public')->delete($partner->logo_path);
        }
        $partner?->delete();
        $this->loadPartners();
        $this->dispatch('notify', message: 'Partenaire supprimé.');
    }

    public function render()
    {
        return view('livewire.admin.content-manager', [
            'sections' => $this->sections(),
        ]);
    }
}

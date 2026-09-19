<?php

namespace App\Actions;

use App\Models\RegistrationForm;
use App\Models\RegistrationLead;
use App\Support\PhoneNumber;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Import en masse de prospects dans une campagne (liste WhatsApp, feuille papier, export
 * d'un autre outil...), point d'entrée unique comme les autres Actions du projet. Même
 * normalisation de téléphone et même détection de doublon par campagne (e-mail OU
 * téléphone déjà présent) que StoreRegistrationLeadRequest — pour ne jamais créer deux
 * fois le même prospect, qu'il vienne du formulaire public ou d'un import admin.
 */
class ImportRegistrationLeads
{
    /**
     * En-têtes reconnus (comparés après ->ascii()->lower()->trim(), donc « Prénoms »,
     * « prenoms » et « PRENOMS » sont équivalents). Colonnes absentes du fichier ignorées.
     */
    private const COLUMNS = [
        'genre' => 'gender',
        'nom' => 'last_name',
        'prenoms' => 'first_name',
        'prenom' => 'first_name',
        'email' => 'email',
        'e-mail' => 'email',
        'telephone' => 'phone_1',
        'tel' => 'phone_1',
        'whatsapp' => 'whatsapp',
        'profession' => 'profession',
        'tranche_age' => 'age_range',
        "tranche d'age" => 'age_range',
        'entreprise' => 'company',
        'ville_pays' => 'city_country',
        'ville et pays' => 'city_country',
        'moyen_paiement' => 'payment_method',
        'moyen de paiement' => 'payment_method',
    ];

    public function handle(RegistrationForm $form, UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            return ['created' => 0, 'duplicates' => 0, 'errors' => ['Impossible de lire le fichier.']];
        }

        // BOM UTF-8 éventuel (fichier généré par Excel).
        if (fread($handle, 3) !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        // Séparateur détecté sur l'en-tête plutôt que fixé en dur : le modèle/export de
        // cette page utilisent le point-virgule (celui qu'Excel attend en locale française
        // à l'ouverture directe d'un fichier), mais un CSV collé depuis un autre outil (ex.
        // Google Sheets) peut arriver en virgule — les deux doivent être acceptés.
        $sample = fgets($handle);
        rewind($handle);
        if (fread($handle, 3) !== "\xEF\xBB\xBF") {
            rewind($handle);
        }
        $delimiter = $sample !== false && substr_count($sample, ';') > substr_count($sample, ',') ? ';' : ',';

        $header = fgetcsv($handle, separator: $delimiter);

        if ($header === false) {
            fclose($handle);

            return ['created' => 0, 'duplicates' => 0, 'errors' => ['Fichier vide ou illisible.']];
        }

        $map = $this->mapHeader($header);

        if (! in_array('last_name', $map, true) || ! in_array('first_name', $map, true) || ! in_array('phone_1', $map, true)) {
            fclose($handle);

            return [
                'created' => 0,
                'duplicates' => 0,
                'errors' => ['Colonnes obligatoires manquantes : nom, prenoms, telephone.'],
            ];
        }

        // Doublons déjà en base pour CETTE campagne, chargés une seule fois.
        $existing = RegistrationLead::query()
            ->where('registration_form_id', $form->id)
            ->get(['email', 'phone_1']);

        $seenEmails = $existing->pluck('email')->filter()->map(fn ($e) => Str::lower($e))->flip();
        $seenPhones = $existing->pluck('phone_1')->map(fn ($p) => PhoneNumber::normalize($p))->filter()->flip();

        $created = 0;
        $duplicates = 0;
        $errors = [];
        $line = 1;

        while (($row = fgetcsv($handle, separator: $delimiter)) !== false) {
            $line++;

            if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            $values = [];
            foreach ($map as $index => $field) {
                $values[$field] = trim((string) ($row[$index] ?? ''));
            }

            $lastName = $values['last_name'] ?? '';
            $firstName = $values['first_name'] ?? '';
            $phone = PhoneNumber::normalize($values['phone_1'] ?? null);

            if ($lastName === '' || $firstName === '' || $phone === null) {
                $errors[] = "Ligne {$line} : nom, prenoms et telephone sont obligatoires.";

                continue;
            }

            $email = $values['email'] ?? '';
            if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Ligne {$line} : e-mail invalide, ignoré pour ce prospect.";
                $email = '';
            }

            $emailKey = $email !== '' ? Str::lower($email) : null;

            if (($emailKey && $seenEmails->has($emailKey)) || $seenPhones->has($phone)) {
                $duplicates++;

                continue;
            }

            RegistrationLead::create([
                'registration_form_id' => $form->id,
                'gender' => in_array($values['gender'] ?? null, ['Homme', 'Femme'], true) ? $values['gender'] : null,
                'last_name' => $lastName,
                'first_name' => $firstName,
                'email' => $email !== '' ? $email : null,
                'phone_1' => $values['phone_1'],
                'whatsapp' => ($values['whatsapp'] ?? '') !== '' ? $values['whatsapp'] : null,
                'profession' => ($values['profession'] ?? '') !== '' ? $values['profession'] : null,
                'age_range' => ($values['age_range'] ?? '') !== '' ? $values['age_range'] : null,
                'company' => ($values['company'] ?? '') !== '' ? $values['company'] : null,
                'city_country' => ($values['city_country'] ?? '') !== '' ? $values['city_country'] : null,
                'payment_method' => ($values['payment_method'] ?? '') !== '' ? $values['payment_method'] : null,
                'how_heard' => 'Import admin',
            ]);

            $created++;

            if ($emailKey) {
                $seenEmails->put($emailKey, true);
            }
            $seenPhones->put($phone, true);
        }

        fclose($handle);

        return ['created' => $created, 'duplicates' => $duplicates, 'errors' => $errors];
    }

    /** @return array<int, string> index de colonne => nom de champ */
    private function mapHeader(array $header): array
    {
        $map = [];

        foreach ($header as $index => $label) {
            $key = Str::of((string) $label)->trim()->lower()->ascii()->value();

            if (isset(self::COLUMNS[$key])) {
                $map[$index] = self::COLUMNS[$key];
            }
        }

        return $map;
    }
}

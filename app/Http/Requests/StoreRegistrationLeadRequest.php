<?php

namespace App\Http\Requests;

use App\Http\Controllers\RegistrationFormController;
use App\Models\RegistrationForm;
use App\Models\RegistrationLead;
use App\Support\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreRegistrationLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'gender' => ['required', 'string', 'in:Homme,Femme'],
            'last_name' => ['required', 'string', 'max:100'],
            'first_name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:180'],
            'phone_1' => ['required', 'string', 'max:40'],
            'phone_2' => ['nullable', 'string', 'max:40'],
            'whatsapp' => ['required', 'string', 'max:40'],
            'country_code' => ['nullable', 'string', 'max:20'],
            'experience_level' => ['required', Rule::in(RegistrationFormController::EXPERIENCE_LEVELS)],
            'profession' => ['required', Rule::in(RegistrationFormController::STATUS_FUNCTIONS)],
            'profession_other' => ['nullable', 'string', 'max:160', 'required_if:profession,Autre'],
            'age_range' => ['required', Rule::in(RegistrationFormController::AGE_RANGES)],
            'company' => ['nullable', 'string', 'max:160'],
            'city_country' => ['required', 'string', 'max:160'],
            'motivations' => ['nullable', 'array'],
            'motivations.*' => ['string', 'max:160'],
            'motivation_other' => ['nullable', 'string', 'max:160'],
            'expectations' => ['nullable', 'string', 'max:2000'],
            'how_heard' => ['nullable', 'string', 'max:120'],
            'payment_method' => ['required', 'string', 'max:120'],
            'payment_frequency' => ['nullable', 'string', 'max:160'],
            // Anti-spam : champ caché qui doit rester vide (honeypot).
            'website' => ['prohibited'],
        ];
    }

    /**
     * Une même campagne ne doit pas pouvoir être pré-remplie deux fois par la même
     * personne. Comparaison faite en PHP (pas en SQL) pour rester portable MySQL/SQLite
     * et parce que `phone_1` est stocké tel quel : on le normalise ici avant de comparer,
     * exactement comme PhoneNumber::normalize() le fait pour la connexion par téléphone.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var RegistrationForm|null $form */
            $form = $this->route('form');

            if (! $form) {
                return;
            }

            $email = $this->input('email');
            $phone = PhoneNumber::normalize($this->input('phone_1'));

            $existing = RegistrationLead::query()
                ->where('registration_form_id', $form->id)
                ->get(['email', 'phone_1']);

            $emailDuplicate = $email && $existing->contains(
                fn (RegistrationLead $lead) => $lead->email && Str::lower($lead->email) === Str::lower($email)
            );

            $phoneDuplicate = $phone && $existing->contains(
                fn (RegistrationLead $lead) => PhoneNumber::normalize($lead->phone_1) === $phone
            );

            if ($emailDuplicate) {
                $validator->errors()->add('email', 'Vous êtes déjà inscrit(e) avec cette adresse e-mail.');
            }

            if ($phoneDuplicate) {
                $validator->errors()->add('phone_1', 'Vous êtes déjà inscrit(e) avec ce numéro de téléphone.');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'gender' => 'genre',
            'last_name' => 'nom',
            'first_name' => 'prénoms',
            'email' => 'e-mail',
            'phone_1' => 'numéro de téléphone',
            'whatsapp' => 'numéro WhatsApp',
            'experience_level' => 'niveau d\'expérience',
            'profession' => 'statut / fonction',
            'profession_other' => 'statut / fonction (précision)',
            'age_range' => 'tranche d\'âge',
            'company' => 'entreprise ou organisation',
            'city_country' => 'ville et pays de résidence',
            'payment_method' => 'moyen de paiement',
        ];
    }
}

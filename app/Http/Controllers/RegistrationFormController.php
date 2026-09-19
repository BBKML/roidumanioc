<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRegistrationLeadRequest;
use App\Mail\RegistrationLeadMail;
use App\Models\RegistrationForm;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RegistrationFormController extends Controller
{
    public const MOTIVATIONS = [
        'Améliorer mes compétences professionnelles',
        'Accroître mes opportunités professionnelles',
        'Me tenir à jour avec les avancées du domaine',
    ];

    public const HOW_HEARD = [
        'Par un proche',
        "Par l'un des formateurs",
        'Réseaux sociaux (TikTok, Facebook…)',
        'Autre',
    ];

    public const PAYMENT_METHODS = [
        'Mobile Money (Orange, MTN, Moov)',
        'Wave',
        'Virement bancaire',
        'Chèque',
        'Western Union / MoneyGram / RIA',
        'Autre',
    ];

    public const EXPERIENCE_LEVELS = [
        'Débutant / Aucune expérience',
        '0 à 5 ans',
        '5 à 10 ans',
        'Plus de 10 ans',
    ];

    public const STATUS_FUNCTIONS = [
        'Étudiant',
        'Entrepreneur',
        'Employé / Salarié',
        'Fonctionnaire',
        'Producteur',
        'Commerçant / Acheteur',
        'Transformateur',
        'Porteur de projet',
        'Autre',
    ];

    public const AGE_RANGES = [
        'Moins de 18 ans',
        '18 à 25 ans',
        '26 à 35 ans',
        '36 à 45 ans',
        '46 à 55 ans',
        '56 ans et plus',
    ];

    public function show(RegistrationForm $form)
    {
        abort_unless($form->isPublished(), 404);

        return view('public.registration-form', ['form' => $form]);
    }

    /**
     * Sert l'affiche/visuel du formulaire directement (indépendant du lien symbolique
     * public/storage — évite le classique « l'image ne s'affiche pas » si storage:link
     * n'a jamais été exécuté sur l'environnement).
     */
    public function image(RegistrationForm $form): Response
    {
        abort_unless($form->cover_image_path, 404);

        $disk = Storage::disk('public');
        abort_unless($disk->exists($form->cover_image_path), 404);

        return response()->file($disk->path($form->cover_image_path), [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    public function store(StoreRegistrationLeadRequest $request, RegistrationForm $form)
    {
        abort_unless($form->isPublished(), 404);

        $data = $request->safe()->except(['motivations', 'motivation_other', 'profession_other', 'website']);

        $motivations = collect($request->safe()->array('motivations'))
            ->push($request->safe()->string('motivation_other')->trim()->value())
            ->filter()
            ->implode(', ');

        $profession = $request->safe()->string('profession')->value();
        if ($profession === 'Autre') {
            $profession = $request->safe()->string('profession_other')->trim()->value() ?: 'Autre';
        }

        $lead = $form->leads()->create([
            ...$data,
            'profession' => $profession,
            'motivations' => $motivations !== '' ? Str::limit($motivations, 250, '') : null,
            'ip' => $request->ip(),
        ]);

        try {
            $to = config('mail.from.address');
            Mail::to($to)->cc(User::activeAdminEmails($to))->send(new RegistrationLeadMail($lead));
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()
            ->route('inscription.show', $form)
            ->with('registration_sent', 'Votre pré-inscription est bien enregistrée. Notre équipe revient vers vous très prochainement par WhatsApp ou e-mail pour confirmer votre place et vous communiquer les modalités de paiement et les informations pratiques.');
    }
}

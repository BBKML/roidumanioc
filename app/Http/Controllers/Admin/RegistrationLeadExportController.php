<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RegistrationLead;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Export symétrique de l'import (ImportRegistrationLeads) — mêmes filtres que la vue
 * (campagne/statut/recherche, passés en query string par le bouton « Exporter » de
 * l'écran), pour que l'admin puisse exporter exactement ce qu'il a sous les yeux.
 */
class RegistrationLeadExportController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $query = RegistrationLead::query()->latest();

        if ($request->filled('form')) {
            $query->where('registration_form_id', $request->integer('form'));
        }

        if ($request->filled('status') && $request->input('status') !== 'tous') {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(fn ($q) => $q
                ->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone_1', 'like', "%{$search}%"));
        }

        $filename = 'prospects-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8 pour Excel
            // Point-virgule, pas virgule : c'est le séparateur de liste attendu par Excel en
            // locale française (celle utilisée en Côte d'Ivoire) — avec une virgule, Excel
            // n'ouvre pas les colonnes et met tout dans la colonne A à l'ouverture directe du
            // fichier. Même choix que RegistrationLeadTemplateController (le modèle d'import).
            fputcsv($out, [
                'nom', 'prenom', 'genre', 'telephone', 'telephone_whatsapp', 'email',
                'profession', 'tranche_age', 'ville',
            ], ';');

            $query->chunk(500, function ($chunk) use ($out) {
                foreach ($chunk as $lead) {
                    fputcsv($out, [
                        $lead->last_name,
                        $lead->first_name,
                        $lead->gender,
                        $lead->phone_1,
                        $lead->whatsapp,
                        $lead->email,
                        $lead->profession,
                        $lead->age_range,
                        $lead->city_country,
                    ], ';');
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}

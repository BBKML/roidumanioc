<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RegistrationLeadTemplateController extends Controller
{
    public function __invoke(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8 pour Excel

            // Point-virgule : séparateur de liste attendu par Excel en locale française
            // (Côte d'Ivoire) à l'ouverture directe du fichier — voir ImportRegistrationLeads,
            // qui détecte automatiquement ce même séparateur (ou une virgule) à la relecture.
            fputcsv($out, [
                'genre', 'nom', 'prenoms', 'email', 'telephone', 'whatsapp',
                'profession', 'tranche_age', 'ville_pays', 'entreprise', 'moyen_paiement',
            ], ';');
            fputcsv($out, [
                'Femme', 'Kouassi', 'Awa', 'awa@example.ci', '0700000000', '0700000000',
                'Productrice', '26 à 35 ans', 'Abidjan, Côte d\'Ivoire', '', 'Mobile Money (Orange, MTN, Moov)',
            ], ';');

            fclose($out);
        }, 'modele-import-prospects.csv', ['Content-Type' => 'text/csv']);
    }
}

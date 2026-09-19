<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NewsletterExportController extends Controller
{
    public function __invoke(): StreamedResponse
    {
        $filename = 'infolettre-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8 pour Excel
            // Point-virgule, pas virgule : séparateur de liste attendu par Excel en locale
            // française (Côte d'Ivoire) — avec une virgule, tout finit dans la colonne A à
            // l'ouverture directe du fichier. Même choix que RegistrationLeadExportController.
            fputcsv($out, ['email', 'source', 'inscrit_le'], ';');

            NewsletterSubscriber::active()->orderBy('email')->chunk(500, function ($chunk) use ($out) {
                foreach ($chunk as $sub) {
                    fputcsv($out, [$sub->email, $sub->source, $sub->created_at->toDateString()], ';');
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Formation;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = [
            ['loc' => route('home'), 'priority' => '1.0', 'changefreq' => 'weekly'],
            ['loc' => route('contact'), 'priority' => '0.5', 'changefreq' => 'yearly'],
            ['loc' => route('placali.show'), 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['loc' => route('formations.index'), 'priority' => '0.9', 'changefreq' => 'weekly'],
            ['loc' => route('marketplace.index'), 'priority' => '0.8', 'changefreq' => 'daily'],
            ['loc' => route('communaute.show'), 'priority' => '0.5', 'changefreq' => 'monthly'],
            ['loc' => route('fondateur.show'), 'priority' => '0.5', 'changefreq' => 'monthly'],
            ['loc' => route('producers.index'), 'priority' => '0.7', 'changefreq' => 'daily'],
            ['loc' => route('needs.index'), 'priority' => '0.6', 'changefreq' => 'daily'],
            ['loc' => route('events.index'), 'priority' => '0.5', 'changefreq' => 'weekly'],
            ['loc' => route('legal.notice'), 'priority' => '0.3', 'changefreq' => 'yearly'],
            ['loc' => route('legal.privacy'), 'priority' => '0.3', 'changefreq' => 'yearly'],
        ];

        foreach (Formation::published()->get(['slug', 'updated_at']) as $formation) {
            $urls[] = [
                'loc' => route('formations.show', $formation),
                'priority' => '0.7',
                'changefreq' => 'monthly',
                'lastmod' => $formation->updated_at?->toAtomString(),
            ];
        }

        $lastmod = optional(Formation::published()->latest('updated_at')->first())->updated_at?->toAtomString()
            ?? now()->toAtomString();

        $xml = view('sitemap', compact('urls', 'lastmod'))->render();

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}

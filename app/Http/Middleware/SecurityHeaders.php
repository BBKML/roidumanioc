<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * En-têtes de sécurité HTTP sur toutes les réponses web.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Ne pas toucher aux téléchargements de fichiers (preuves de paiement, etc.).
        if ($response->headers->has('Content-Disposition')) {
            return $response;
        }

        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'geolocation=(), microphone=(), camera=(), payment=()',
            'Cross-Origin-Opener-Policy' => 'same-origin',
            'X-Permitted-Cross-Domain-Policies' => 'none',
            // CSP volontairement modérée : on verrouille l'injection d'objets, les <base> et
            // les cibles de formulaire, on autorise l'embarquement vidéo (YouTube/Vimeo/Bunny).
            // (pas de restriction script-src : Livewire/Alpine ont besoin de eval + inline)
            'Content-Security-Policy' => implode('; ', [
                "default-src 'self'",
                "object-src 'none'",
                "base-uri 'self'",
                "form-action 'self'",
                "frame-ancestors 'self'",
                "img-src 'self' data: https: blob:",
                "media-src 'self' blob:",
                "font-src 'self' https://fonts.gstatic.com",
                "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
                "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
                "connect-src 'self'",
                "frame-src 'self' https://www.youtube-nocookie.com https://www.youtube.com https://player.vimeo.com https://iframe.mediadelivery.net",
            ]),
        ];

        // HSTS uniquement en HTTPS (production).
        if ($request->secure()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value, false);
        }

        return $response;
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Connexion Google minimaliste (OAuth2 « authorization code »), sans dépendance.
 *
 * Sécurité :
 * - paramètre `state` aléatoire vérifié au retour (anti-CSRF) ;
 * - le profil est lu sur l'endpoint userinfo de Google en serveur-à-serveur (TLS),
 *   pas besoin de vérifier un JWT nous-mêmes ;
 * - l'appelant DOIT contrôler `email_verified` avant de créer / lier un compte.
 */
class GoogleOAuth
{
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';

    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const USERINFO_URL = 'https://openidconnect.googleapis.com/v1/userinfo';

    public static function enabled(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'));
    }

    public function redirectUrl(string $state): string
    {
        return self::AUTH_URL.'?'.http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => url(config('services.google.redirect')),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'prompt' => 'select_account',
            'access_type' => 'online',
        ]);
    }

    public static function freshState(): string
    {
        return Str::random(40);
    }

    /**
     * Échange le code d'autorisation contre le profil Google.
     *
     * @return array{sub:string,email:string,email_verified:bool,name:?string,picture:?string}
     */
    public function fetchProfile(string $code): array
    {
        $token = Http::asForm()->post(self::TOKEN_URL, [
            'code' => $code,
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uri' => url(config('services.google.redirect')),
            'grant_type' => 'authorization_code',
        ]);

        if ($token->failed() || blank($token->json('access_token'))) {
            throw new RuntimeException('Échec de l\'échange du code Google.');
        }

        $profile = Http::withToken($token->json('access_token'))->get(self::USERINFO_URL);

        if ($profile->failed() || blank($profile->json('sub'))) {
            throw new RuntimeException('Impossible de lire le profil Google.');
        }

        return [
            'sub' => (string) $profile->json('sub'),
            'email' => Str::lower((string) $profile->json('email')),
            'email_verified' => (bool) $profile->json('email_verified'),
            'name' => $profile->json('name'),
            'picture' => $profile->json('picture'),
        ];
    }
}

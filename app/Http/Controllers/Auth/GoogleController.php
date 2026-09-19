<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\GoogleOAuth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class GoogleController extends Controller
{
    public function __construct(private GoogleOAuth $google) {}

    public function redirect(Request $request): RedirectResponse
    {
        abort_unless(GoogleOAuth::enabled(), 404);

        $state = GoogleOAuth::freshState();
        $request->session()->put('google_oauth_state', $state);

        return redirect()->away($this->google->redirectUrl($state));
    }

    public function callback(Request $request): RedirectResponse
    {
        abort_unless(GoogleOAuth::enabled(), 404);

        // Anti-CSRF : le state doit correspondre à celui posé avant la redirection.
        $expected = $request->session()->pull('google_oauth_state');
        if (! $expected || ! hash_equals($expected, (string) $request->query('state'))) {
            return redirect()->route('login')->withErrors(['email' => 'Connexion Google expirée, réessayez.']);
        }

        if ($request->has('error') || ! $request->filled('code')) {
            return redirect()->route('login')->withErrors(['email' => 'Connexion Google annulée.']);
        }

        try {
            $profile = $this->google->fetchProfile($request->query('code'));
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('login')->withErrors(['email' => 'La connexion Google a échoué, réessayez.']);
        }

        if (! $profile['email_verified'] || blank($profile['email'])) {
            return redirect()->route('login')->withErrors(['email' => 'Votre adresse Google n\'est pas vérifiée.']);
        }

        $user = $this->resolveUser($profile);

        if (! $user->isActive()) {
            return redirect()->route('login')->withErrors(['email' => 'Ce compte est suspendu. Contactez l\'administrateur.']);
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Retrouve le compte par provider_id, sinon par e-mail (liaison), sinon en crée un.
     * Un compte créé via Google est TOUJOURS un apprenant actif — jamais admin.
     */
    private function resolveUser(array $profile): User
    {
        $byProvider = User::where('provider', 'google')
            ->where('provider_id', $profile['sub'])
            ->first();

        if ($byProvider) {
            return $byProvider;
        }

        $byEmail = User::where('email', $profile['email'])->first();

        if ($byEmail) {
            $byEmail->forceFill([
                'provider' => 'google',
                'provider_id' => $profile['sub'],
                'email_verified_at' => $byEmail->email_verified_at ?? now(),
            ])->save();

            return $byEmail;
        }

        // forceFill (pas create()) : role/status ne sont plus dans $fillable (voir User::$fillable)
        // pour qu'aucun futur create()/fill() alimenté par une requête ne puisse les définir.
        $user = new User;
        $user->forceFill([
            'name' => $profile['name'] ?: Str::before($profile['email'], '@'),
            'email' => $profile['email'],
            'password' => Str::password(32),
            'provider' => 'google',
            'provider_id' => $profile['sub'],
            'role' => 'apprenant',
            'status' => 'actif',
            'email_verified_at' => now(),
            'joined_at' => now(),
        ])->save();

        return $user;
    }
}

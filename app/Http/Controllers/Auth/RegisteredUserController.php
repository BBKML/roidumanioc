<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\NewMemberMail;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * Beaucoup de clients n'ont pas d'e-mail mais ont tous un téléphone :
     * l'un des deux suffit (required_without), le champ « login » retenu
     * détermine ensuite comment l'utilisateur pourra se reconnecter.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->merge(['phone' => PhoneNumber::normalize($request->input('phone'))]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'required_without:phone', 'string', 'lowercase', 'email', 'max:180', Rule::unique(User::class, 'email')],
            'phone' => ['nullable', 'required_without:email', 'string', 'max:20', Rule::unique(User::class, 'phone')],
            'city' => ['nullable', 'string', 'max:120'],
            // Simple déclaration d'intention au moment de l'inscription — ne crée PAS de
            // producer_profile/buyer_profile ici (ces modèles ont des champs obligatoires,
            // bio/zone/activity_type..., et la mention légale à accepter en premier, §44).
            // On se contente de rediriger vers le formulaire d'activation correspondant
            // juste après l'inscription plutôt que sur le tableau de bord générique — le
            // reste (création réelle du profil) passe par le flux existant, inchangé.
            'account_type' => ['nullable', 'array'],
            'account_type.*' => ['in:producteur,acheteur'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'city' => $validated['city'] ?? null,
            'password' => Hash::make($validated['password']),
            'password_changed_at' => now(),
            'joined_at' => now(),
            // role / status : valeurs par défaut de la base (apprenant / actif) — jamais depuis la requête.
        ]);

        event(new Registered($user));

        try {
            $adminEmail = User::query()->activeAdmins()->value('email') ?: config('mail.from.address');
            Mail::to($adminEmail)->send(new NewMemberMail($user));
        } catch (\Throwable $e) {
            report($e);
        }

        Auth::login($user);

        // Type de compte choisi à l'inscription (§ propositions producteur/acheteur) :
        // amène directement sur le formulaire d'activation correspondant plutôt que sur le
        // tableau de bord générique. S'il a coché les deux, producteur passe en premier —
        // le CTA « Devenir acheteur » du tableau de bord prend le relais ensuite, comme pour
        // n'importe quel membre qui active un second espace plus tard.
        $accountTypes = $validated['account_type'] ?? [];
        $fallback = match (true) {
            in_array('producteur', $accountTypes, true) => route('learner.producer', absolute: false),
            in_array('acheteur', $accountTypes, true) => route('learner.buyer', absolute: false),
            default => route('dashboard', absolute: false),
        };

        // Comme la connexion (AuthenticatedSessionController) : si l'invité a été redirigé
        // ici depuis une ressource protégée (ex. « S'inscrire à cette formation »), on le
        // ramène dessus en priorité plutôt que sur la destination ci-dessus.
        return redirect()->intended($fallback);
    }
}

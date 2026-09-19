<x-mail::message>
# Mot de passe réinitialisé

Bonjour {{ $user->name }}, votre mot de passe sur Le Roi du Manioc vient d'être réinitialisé par un administrateur.

**Identifiant de connexion :** {{ $user->email ?: $user->phone }}
**Nouveau mot de passe temporaire :** {{ $password }}

Merci de le changer dès votre prochaine connexion, depuis « Mon compte ». Si vous n'êtes pas
à l'origine de cette demande, contactez-nous immédiatement.

<x-mail::button :url="route('login')">
Me connecter
</x-mail::button>
</x-mail::message>

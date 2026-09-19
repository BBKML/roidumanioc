<x-mail::message>
# Bienvenue, {{ $user->name }}

Un compte vient d'être créé pour vous sur Le Roi du Manioc.

**Identifiant de connexion :** {{ $user->email ?: $user->phone }}
**Mot de passe temporaire :** {{ $password }}

Merci de le changer dès votre première connexion, depuis « Mon compte ».

<x-mail::button :url="route('login')">
Me connecter
</x-mail::button>
</x-mail::message>

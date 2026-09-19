<x-mail::message>
# Nouveau membre inscrit

**Nom :** {{ $user->name }}
@if ($user->email)
**E-mail :** {{ $user->email }}
@endif
@if ($user->phone)
**Téléphone :** {{ $user->phone }}
@endif
@if ($user->city)
**Ville :** {{ $user->city }}
@endif
**Rôle :** {{ $user->role->label() }}

<x-mail::button :url="route('admin.members')">
Ouvrir les membres
</x-mail::button>

Inscrit le {{ $user->created_at?->format('d/m/Y à H:i') }}.
</x-mail::message>

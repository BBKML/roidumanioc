<x-mail::message>
# Nouveau message — formulaire de contact

**Nom :** {{ $contact->name }}
**E-mail :** {{ $contact->email }}
@if ($contact->phone)
**Téléphone :** {{ $contact->phone }}
@endif
@if ($contact->subject)
**Sujet :** {{ $contact->subject }}
@endif

---

{{ $contact->message }}

---

<x-mail::button :url="url('/admin')">
Ouvrir le back-office
</x-mail::button>

Reçu le {{ $contact->created_at?->format('d/m/Y à H:i') }}.
</x-mail::message>

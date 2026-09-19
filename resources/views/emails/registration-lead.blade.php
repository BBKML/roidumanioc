<x-mail::message>
# Nouvelle inscription — {{ $lead->registrationForm->title }}

**Nom :** {{ $lead->fullName() }}
**Genre :** {{ $lead->gender }}
@if ($lead->email)
**E-mail :** {{ $lead->email }}
@endif
**Téléphone :** {{ $lead->phone_1 }}
@if ($lead->whatsapp)
**WhatsApp :** {{ $lead->whatsapp }}
@endif
**Statut / fonction :** {{ $lead->profession }}
**Niveau d'expérience :** {{ $lead->experience_level }}
**Tranche d'âge :** {{ $lead->age_range }}
**Entreprise/Organisation :** {{ $lead->company }}
**Ville et pays :** {{ $lead->city_country }}
**Moyen de paiement souhaité :** {{ $lead->payment_method }}

<x-mail::button :url="route('admin.registration-leads')">
Ouvrir le back-office
</x-mail::button>

Reçu le {{ $lead->created_at?->format('d/m/Y à H:i') }}.
</x-mail::message>

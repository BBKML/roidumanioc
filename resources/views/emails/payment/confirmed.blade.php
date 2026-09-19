@php $isFormation = $payment->enrollment_id !== null; @endphp
<x-mail::message>
# Paiement confirmé 🎉

Bonjour {{ $payment->user->name }},

Votre paiement **{{ $payment->reference }}** pour **{{ $payment->label }}** est confirmé.

@if ($isFormation)
Votre accès à la formation est **ouvert dès maintenant**.

<x-mail::button :url="route('learner.dashboard')">
Accéder à ma formation
</x-mail::button>
@else
Votre commande passe en préparation. Nous vous préviendrons de l'expédition.

<x-mail::button :url="route('learner.orders')">
Voir ma commande
</x-mail::button>
@endif

Merci de votre confiance,<br>
L'équipe du Roi du Manioc
</x-mail::message>

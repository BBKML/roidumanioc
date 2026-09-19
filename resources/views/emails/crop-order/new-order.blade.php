<x-mail::message>
# Nouvelle commande reçue

Bonjour,

**{{ $cropOrder->buyerProfile->company_name ?: 'Un acheteur' }}** souhaite commander
**{{ $cropOrder->product_name }}** ({{ rtrim(rtrim(number_format((float) $cropOrder->requested_quantity, 2, '.', ' '), '0'), '.') }} {{ $cropOrder->requested_unit }}).

<x-mail::panel>
Lieu de livraison : {{ $cropOrder->delivery_location }}<br>
@if ($cropOrder->desired_date)
Date souhaitée : {{ $cropOrder->desired_date->translatedFormat('d F Y') }}<br>
@endif
Moyen de paiement souhaité : {{ $cropOrder->payment_method }}
</x-mail::panel>

<x-mail::button :url="route('learner.crop-orders.show', $cropOrder)">
Voir la commande
</x-mail::button>

Merci,<br>
L'équipe du Roi du Manioc
</x-mail::message>

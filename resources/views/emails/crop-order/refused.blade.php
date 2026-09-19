<x-mail::message>
# Commande refusée

Bonjour,

**{{ $cropOrder->producerProfile->business_name }}** a refusé votre commande pour
**{{ $cropOrder->product_name }}**.

@if ($cropOrder->refusal_reason)
<x-mail::panel>
Motif indiqué : {{ $cropOrder->refusal_reason }}
</x-mail::panel>
@endif

<x-mail::button :url="route('learner.crop-orders.show', $cropOrder)">
Voir la commande
</x-mail::button>

Merci,<br>
L'équipe du Roi du Manioc
</x-mail::message>

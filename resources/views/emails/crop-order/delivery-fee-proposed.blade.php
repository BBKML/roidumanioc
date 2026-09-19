<x-mail::message>
# {{ $isFirst ? 'Conditions de livraison reçues' : 'Nouvelle proposition de frais de livraison' }}

Bonjour,

**{{ $actor->name }}** {{ $isFirst ? 'a renseigné les conditions de livraison' : 'propose un nouveau montant de frais de livraison' }}
pour **{{ $cropOrder->product_name }}**.

<x-mail::panel>
@if ($isFirst && $cropOrder->product_price_total)
Prix du produit : {{ number_format($cropOrder->product_price_total, 0, ',', ' ') }} FCFA<br>
@endif
Frais de livraison proposés : {{ number_format((float) $cropOrder->latestDeliveryProposal()?->amount, 0, ',', ' ') }} FCFA
</x-mail::panel>

<x-mail::button :url="route('learner.crop-orders.show', $cropOrder)">
Répondre à la proposition
</x-mail::button>

Merci,<br>
L'équipe du Roi du Manioc
</x-mail::message>

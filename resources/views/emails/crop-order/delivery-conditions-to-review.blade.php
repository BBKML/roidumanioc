<x-mail::message>
# Conditions de livraison à valider

Bonjour,

**{{ $cropOrder->producerProfile->business_name }}** a soumis ses conditions de livraison
pour la commande **{{ $cropOrder->product_name }}** — elles ne sont pas encore visibles de
l'acheteur.

<x-mail::panel>
Prix du produit : {{ number_format((float) $cropOrder->pending_product_price_total, 0, ',', ' ') }} FCFA<br>
Frais de livraison proposés : {{ number_format((float) $cropOrder->pending_delivery_fee, 0, ',', ' ') }} FCFA
</x-mail::panel>

<x-mail::button :url="route('learner.crop-orders.show', $cropOrder)">
Examiner et valider
</x-mail::button>

Merci,<br>
L'équipe du Roi du Manioc
</x-mail::message>

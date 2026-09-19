<x-mail::message>
# Commande confirmée

Bonjour,

Un accord a été trouvé pour **{{ $cropOrder->product_name }}**. Les conditions sont désormais
figées :

<x-mail::panel>
Quantité : {{ rtrim(rtrim(number_format((float) $cropOrder->requested_quantity, 2, '.', ' '), '0'), '.') }} {{ $cropOrder->requested_unit }}<br>
Prix du produit : {{ number_format((float) $cropOrder->product_price_total, 0, ',', ' ') }} FCFA<br>
Frais de livraison : {{ number_format((float) $cropOrder->delivery_fee_agreed, 0, ',', ' ') }} FCFA<br>
**Total : {{ number_format((float) $cropOrder->total_amount, 0, ',', ' ') }} FCFA**
</x-mail::panel>

Vous pouvez maintenant demander l'aide de l'administration pour organiser la livraison depuis
la fiche de la commande.

<x-mail::button :url="route('learner.crop-orders.show', $cropOrder)">
Voir la commande
</x-mail::button>

Merci,<br>
L'équipe du Roi du Manioc
</x-mail::message>

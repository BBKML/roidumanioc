<x-mail::message>
# Demande d'aide à la livraison

Bonjour,

Une commande confirmée a besoin d'aide pour organiser sa livraison.

<x-mail::panel>
Produit : {{ $cropOrder->product_name }}<br>
Producteur : {{ $cropOrder->producerProfile->business_name }}<br>
Acheteur : {{ $cropOrder->buyerProfile->company_name ?: $cropOrder->buyerProfile->user->name }}<br>
Lieu de livraison : {{ $cropOrder->delivery_location }}<br>
Total convenu : {{ number_format((float) $cropOrder->total_amount, 0, ',', ' ') }} FCFA
</x-mail::panel>

<x-mail::button :url="route('admin.delivery-assists')">
Gérer l'aide à la livraison
</x-mail::button>

Merci,<br>
L'équipe du Roi du Manioc
</x-mail::message>

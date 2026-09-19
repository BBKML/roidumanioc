<x-mail::message>
# Votre commande a été livrée

Bonjour,

Votre commande **{{ $cropOrder->product_name }}** a été marquée comme livrée par
l'administration.

<x-mail::button :url="route('learner.crop-orders.show', $cropOrder)">
Voir la commande
</x-mail::button>

Merci,<br>
L'équipe du Roi du Manioc
</x-mail::message>

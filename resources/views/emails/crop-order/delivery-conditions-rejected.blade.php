<x-mail::message>
# Conditions de livraison renvoyées

Bonjour,

L'administration a renvoyé vos conditions de livraison pour la commande
**{{ $cropOrder->product_name }}** — elles n'ont pas été transmises à l'acheteur.

<x-mail::panel>
{{ $reason }}
</x-mail::panel>

Vous pouvez soumettre de nouvelles conditions depuis la fiche de la commande.

<x-mail::button :url="route('learner.crop-orders.show', $cropOrder)">
Voir la commande
</x-mail::button>

Merci,<br>
L'équipe du Roi du Manioc
</x-mail::message>

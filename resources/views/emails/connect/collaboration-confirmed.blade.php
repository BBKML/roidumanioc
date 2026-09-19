<x-mail::message>
# Collaboration confirmée

Bonjour,

Votre collaboration pour **{{ $connectionRequest->productLabel() }}** est désormais confirmée.
Les prochaines étapes (paiement puis livraison) se suivent depuis la plateforme.

<x-mail::panel>
Toute la suite — déclaration de paiement, confirmation, suivi de livraison — se passe sur la
plateforme. Vos coordonnées ne sont jamais partagées automatiquement.
</x-mail::panel>

<x-mail::button :url="route('learner.requests.show', $connectionRequest)">
Suivre la collaboration
</x-mail::button>

Merci,<br>
L'équipe du Roi du Manioc
</x-mail::message>

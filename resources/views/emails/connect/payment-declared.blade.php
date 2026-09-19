<x-mail::message>
# Paiement déclaré par votre acheteur

Bonjour,

Un paiement vient d'être déclaré pour **{{ $collaboration->agreed_product }}**
({{ $collaboration->agreed_quantity }} {{ $collaboration->agreed_unit }}).

<x-mail::panel>
**Confirmez uniquement après avoir vérifié la réception réelle du paiement.**
En cas de désaccord, vous pouvez le contester depuis la plateforme.
</x-mail::panel>

<x-mail::button :url="route('learner.collaborations.show', $collaboration)">
Vérifier et répondre
</x-mail::button>

Merci,<br>
L'équipe du Roi du Manioc
</x-mail::message>

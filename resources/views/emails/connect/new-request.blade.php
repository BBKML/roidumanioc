<x-mail::message>
# Nouvelle demande de mise en relation

Bonjour,

**{{ $connectionRequest->requester->name }}** souhaite être mis en relation avec vous pour
**{{ $connectionRequest->productLabel() }}**.

<x-mail::panel>
Consultez le détail et répondez directement depuis la plateforme — vos coordonnées ne sont
jamais partagées automatiquement.
</x-mail::panel>

<x-mail::button :url="route('learner.requests.show', $connectionRequest)">
Voir la demande
</x-mail::button>

Merci,<br>
L'équipe du Roi du Manioc
</x-mail::message>

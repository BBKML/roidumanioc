<x-mail::message>
# Votre paiement n'a pas pu être validé

Bonjour {{ $payment->user->name }},

Nous n'avons pas pu confirmer le paiement **{{ $payment->reference }}** pour **{{ $payment->label }}**.

@if ($payment->rejection_reason)
**Motif :** {{ $payment->rejection_reason }}
@else
Nous n'avons pas retrouvé le versement sur notre compte marchand.
@endif

Si vous avez bien effectué le paiement, répondez à cet e-mail avec la capture du reçu
et le numéro de transaction, ou contactez-nous sur WhatsApp. Vous pouvez aussi
refaire une déclaration depuis votre espace.

<x-mail::button :url="route('learner.orders')">
Voir mes paiements
</x-mail::button>

L'équipe du Roi du Manioc
</x-mail::message>

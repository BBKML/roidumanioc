<x-mail::message>
# Merci, votre paiement est bien enregistré

Bonjour {{ $payment->user->name }},

Nous avons reçu votre déclaration de paiement pour **{{ $payment->label }}**.

- **Référence :** {{ $payment->reference }}
- **Montant attendu :** {{ number_format($payment->amount, 0, ',', ' ') }} FCFA
- **Moyen :** {{ $payment->method->label() }}

Notre équipe vérifie la réception sur le compte marchand — généralement sous quelques heures.
**Votre accès s'ouvre automatiquement dès la confirmation.** Vous recevrez un e-mail à ce moment-là.

Gardez la référence **{{ $payment->reference }}** : elle relie votre dépôt à votre commande.

Merci,<br>
L'équipe du Roi du Manioc
</x-mail::message>

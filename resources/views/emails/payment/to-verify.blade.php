<x-mail::message>
# Nouveau paiement à vérifier

**Référence :** {{ $payment->reference }}
**Client :** {{ $payment->user->name }} ({{ $payment->user->email }})
**Objet :** {{ $payment->label }}
**Prix attendu :** {{ number_format($payment->amount, 0, ',', ' ') }} FCFA
**Montant déclaré :** {{ $payment->declared_amount !== null ? number_format($payment->declared_amount, 0, ',', ' ').' FCFA' : '—' }}
**Moyen :** {{ $payment->method->label() }}
**N° transaction :** {{ $payment->transaction_id ?: 'non fourni' }}
**Niveau de risque du contrôle auto :** {{ strtoupper($payment->risk()) }}

@if ($payment->flags())
**Signaux détectés :**
@foreach ($payment->flags() as $flag)
- {{ \App\Models\Payment::flagLabel($flag) }}
@endforeach
@endif

<x-mail::panel>
Le contrôle automatique porte uniquement sur les informations saisies par le client.
**Confirmez seulement après avoir vu l'argent sur le compte marchand {{ $payment->method->label() }}.**
</x-mail::panel>

<x-mail::button :url="route('admin.payments')">
Ouvrir la file de vérification
</x-mail::button>
</x-mail::message>

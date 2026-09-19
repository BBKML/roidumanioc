@php $direct = $order->payment_mode->value === 'direct'; @endphp
<x-mail::message>
# {{ $direct ? 'Demande sur une annonce producteur' : 'Nouvelle commande' }} — {{ $order->reference }}

**Client :** {{ $order->customer_name }}{{ $order->user ? ' ('.$order->user->email.')' : '' }}
**Téléphone :** {{ $order->contact_phone ?: '—' }}
**{{ $direct ? 'Annonce' : 'Article' }} :** {{ $order->item_label }}
**Livraison à :** {{ $order->delivery_address }}, {{ $order->delivery_city }}
@if ($order->customer_note)
**Note du client :** {{ $order->customer_note }}
@endif

@if ($direct)
<x-mail::panel>
Le règlement se fait **directement entre l'acheteur et le producteur**. Le Roi du Manioc
met en relation et assure le suivi. Vérifiez que le producteur a bien été prévenu.
</x-mail::panel>
@else
**Sous-total :** {{ number_format($order->amount, 0, ',', ' ') }} FCFA
@if ($order->delivery_fee)
**Livraison :** {{ number_format($order->delivery_fee, 0, ',', ' ') }} FCFA
@endif
**Total :** {{ number_format($order->total(), 0, ',', ' ') }} FCFA
**Paiement :** {{ $order->payment_mode->label() }}
@if ($order->isPayOnDelivery())

<x-mail::panel>
Paiement à encaisser **à la remise** : {{ number_format($order->total(), 0, ',', ' ') }} FCFA.
</x-mail::panel>
@endif
@endif

<x-mail::button :url="route('admin.orders')">
Ouvrir les commandes
</x-mail::button>
</x-mail::message>

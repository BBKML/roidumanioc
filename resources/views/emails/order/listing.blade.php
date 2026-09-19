<x-mail::message>
# Un acheteur souhaite votre annonce

**Annonce :** {{ $order->item_label }}
**Acheteur :** {{ $order->customer_name }}
**Téléphone :** {{ $order->contact_phone ?: '—' }}
**Livraison souhaitée :** {{ $order->delivery_address }}, {{ $order->delivery_city }}
@if ($order->customer_note)
**Message :** {{ $order->customer_note }}
@endif

Le règlement se fait **directement entre vous et l'acheteur** (mobile money ou à la livraison).
Le Roi du Manioc met simplement en relation.

Contactez l'acheteur au **{{ $order->contact_phone }}** pour convenir du prix, de la quantité et de la livraison.

Référence de suivi : {{ $order->reference }}
</x-mail::message>

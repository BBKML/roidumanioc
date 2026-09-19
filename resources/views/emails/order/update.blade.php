@php
    $total = number_format($order->total(), 0, ',', ' ').' FCFA';
@endphp
<x-mail::message>
@switch($stage)
@case('placed')
# Merci, votre {{ $order->payment_mode->value === 'direct' ? 'demande est enregistrée' : 'commande est enregistrée' }}

Bonjour {{ $order->customer_name }},

Nous avons bien reçu votre {{ $order->payment_mode->value === 'direct' ? 'demande' : 'commande' }} **{{ $order->reference }}** — {{ $order->item_label }}.

@switch($order->payment_mode->value)
@case('direct')
Le **producteur va vous contacter** au {{ $order->contact_phone }} pour convenir du prix, de la quantité et de la livraison. Le règlement se fait directement avec lui.
@break
@case('on_delivery')
Vous réglerez **{{ $total }}** à la réception. Nous vous préviendrons de l'expédition.
@break
@default
Votre paiement est en cours de vérification. La préparation démarre dès confirmation.
@endswitch
@break

@case('shipped')
# Votre commande est en route 🚚

Bonjour {{ $order->customer_name }},

La commande **{{ $order->reference }}** ({{ $order->item_label }}) part vers
{{ $order->delivery_address }}, {{ $order->delivery_city }}.
@if ($order->isPayOnDelivery())
Préparez **{{ $total }}** pour la remise.
@endif
@break

@case('delivered')
# Commande livrée ✅

Bonjour {{ $order->customer_name }},

Votre commande **{{ $order->reference }}** a été livrée. Merci de votre confiance !
@break

@case('cancelled')
# Commande annulée

Bonjour {{ $order->customer_name }},

Votre commande **{{ $order->reference }}** a été annulée. Contactez-nous pour toute question.
@break
@endswitch

<x-mail::button :url="route('learner.orders')">
Voir mes commandes
</x-mail::button>

L'équipe du Roi du Manioc
</x-mail::message>

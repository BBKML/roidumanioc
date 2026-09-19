@props([
    'product',
    'quantity' => null,
    'unit' => null,
    'priceTotal' => null,
    'zone' => null,
    'producerName' => null,
    'buyerName' => null,
    'status' => null,
    'date' => null,
    'printable' => false,
])

{{-- Résumé de commande façon « bon de commande » — remplace un simple paragraphe par un
     vrai document structuré (clé/valeur), réutilisé tel quel sur l'écran de demande
     (Connect\Show, aperçu avant confirmation) et sur l'écran de collaboration
     (Collaboration\Show, les termes réellement actés). Volontairement fait de props
     scalaires plutôt que de dépendre d'un modèle précis, pour rester réutilisable des deux
     côtés malgré leurs formes de données différentes. --}}
<div class="order-summary">
  <div class="order-summary-head">
    <span class="order-summary-title">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 2h6l1 4H8l1-4Z"/><path d="M4 6h16l-1.5 14a1 1 0 0 1-1 1H6.5a1 1 0 0 1-1-1L4 6Z"/></svg>
      Résumé de la commande
    </span>
    @if ($status)
      <x-adm.pill :status="$status" />
    @endif
  </div>

  <dl class="kv order-summary-kv">
    <dt>Produit</dt>
    <dd>{{ $product }}</dd>

    @if ($quantity !== null)
      <dt>Quantité</dt>
      <dd>{{ number_format((float) $quantity, 2, ',', ' ') }} {{ $unit }}</dd>
    @endif

    <dt>Prix total</dt>
    <dd>{{ $priceTotal ? number_format($priceTotal, 0, ',', ' ').' FCFA' : 'À convenir' }}</dd>

    @if ($zone)
      <dt>Zone</dt>
      <dd>{{ $zone }}</dd>
    @endif

    @if ($producerName)
      <dt>Producteur</dt>
      <dd>{{ $producerName }}</dd>
    @endif

    @if ($buyerName)
      <dt>Acheteur</dt>
      <dd>{{ $buyerName }}</dd>
    @endif

    @if ($date)
      <dt>Date</dt>
      <dd>{{ $date instanceof \Carbon\Carbon ? $date->translatedFormat('d F Y') : $date }}</dd>
    @endif
  </dl>

  @if ($printable)
    <button type="button" class="btn sm ghost order-summary-print no-print" onclick="window.print()">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 14h12v8H6z"/></svg>
      Imprimer
    </button>
  @endif
</div>

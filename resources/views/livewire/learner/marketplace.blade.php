<div style="display:flex;flex-direction:column;gap:1.4rem">

  <div class="page-intro">
    <div>
      <h2>Marketplace</h2>
      <p><b>Boutique officielle</b> : commande et paiement en ligne (ou à la livraison). <b>Annonces des producteurs</b> : on transmet votre demande au vendeur, le règlement se fait directement avec lui.</p>
    </div>
  </div>

  <div class="grid g-3">
    @forelse ($listings as $offer)
      <div class="card" wire:key="offer-{{ $offer->id }}">
        <span class="pill neutral"><span class="dot"></span>{{ $offer->type }}</span>
        <h3 style="font-size:1rem;margin:.5rem 0 .2rem">{{ $offer->title }}</h3>
        <p class="muted" style="font-size:.8rem">{{ $offer->location }} · {{ $offer->seller?->name ?? $offer->seller_name }}</p>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:.9rem;gap:.6rem;flex-wrap:wrap">
          <b>{{ $offer->price_label }}</b>
          <a class="btn sm gold" href="{{ route('learner.listing-checkout', $offer) }}" wire:navigate>Commander</a>
        </div>
      </div>
    @empty
      <div class="card"><div class="empty"><p>Aucune annonce publiée pour le moment.</p></div></div>
    @endforelse
  </div>

  <div class="card">
    <div class="card-head"><h3>Boutique officielle — intrants</h3></div>
    <div class="table-wrap" style="border:none">
      <table>
        <thead><tr><th>Produit</th><th>Prix</th><th>Stock</th><th></th></tr></thead>
        <tbody>
          @forelse ($products as $product)
            <tr class="row" wire:key="prod-{{ $product->id }}">
              <td style="font-weight:700">{{ $product->name }}</td>
              <td class="nums">{{ number_format($product->price, 0, ',', ' ') }} FCFA</td>
              <td class="muted">{{ $product->stock > 0 ? 'En stock' : 'Rupture' }}</td>
              <td style="text-align:right">
                @if ($product->stock > 0)
                  <a class="btn sm gold" href="{{ route('learner.shop-checkout', $product) }}" wire:navigate>Commander</a>
                @else
                  <button class="btn sm ghost" disabled>Rupture</button>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="4"><div class="empty"><p>Boutique vide.</p></div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

</div>

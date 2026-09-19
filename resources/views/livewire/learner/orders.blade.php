<div style="display:flex;flex-direction:column;gap:1.4rem">

  <div class="page-intro">
    <div>
      <h2>Mes commandes</h2>
      <p>Suivi de vos achats et de vos paiements sur le royaume.</p>
    </div>
  </div>

  @if ($payments->isNotEmpty())
    <div class="card">
      <div class="card-head"><h3>Mes paiements</h3></div>
      <div class="table-wrap" style="border:none">
        <table>
          <thead><tr><th>Référence</th><th>Objet</th><th>Montant</th><th>Moyen</th><th>Statut</th><th></th></tr></thead>
          <tbody>
            @foreach ($payments as $p)
              <tr class="row" wire:key="pay-{{ $p->id }}">
                <td style="font-weight:800;font-family:'Fraunces',serif">{{ $p->reference }}</td>
                <td class="muted">
                  {{ $p->label }}
                  @if ($p->status->value === 'refuse' && $p->rejection_reason)
                    <div class="inline-err" style="font-weight:600">Refusé : {{ $p->rejection_reason }}</div>
                  @endif
                </td>
                <td class="nums">{{ number_format($p->amount, 0, ',', ' ') }} FCFA</td>
                <td>{{ $p->method->label() }}</td>
                <td><x-adm.pill :status="$p->status" /></td>
                <td style="text-align:right;white-space:nowrap">
                  @if ($p->proof_path)
                    <a class="link-btn" href="{{ route('payments.proof', $p) }}" target="_blank" rel="noopener">Ma capture</a>
                  @endif
                  @if ($p->status->value === 'refuse' && $p->enrollment && $p->enrollment->formation)
                    · <a class="link-btn" href="{{ route('learner.checkout', $p->enrollment->formation) }}" wire:navigate>Renvoyer</a>
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  @endif

  @if ($orders->isNotEmpty())
    <div class="card">
      <div class="card-head"><h3>Mes commandes &amp; demandes</h3></div>
      <div class="table-wrap" style="border:none">
        <table>
          <thead><tr><th>Réf.</th><th>Article</th><th>Livraison</th><th>Paiement</th><th>Total</th><th>Statut</th></tr></thead>
          <tbody>
            @foreach ($orders as $order)
              @php $mode = $order->payment_mode->value; @endphp
              <tr class="row" wire:key="ord-{{ $order->id }}">
                <td style="font-weight:800;font-family:'Fraunces',serif">{{ $order->reference }}</td>
                <td style="font-weight:700">{{ $order->item_label }}</td>
                <td class="muted" style="font-size:.8rem">{{ $order->delivery_city ?: '—' }}</td>
                <td>
                  <span class="pill {{ $mode === 'on_delivery' ? 'warn' : ($mode === 'direct' ? 'neutral' : 'info') }}">
                    <span class="dot"></span>{{ $order->payment_mode->label() }}
                  </span>
                </td>
                <td class="nums">{{ $order->total() > 0 ? number_format($order->total(), 0, ',', ' ').' FCFA' : 'à convenir' }}</td>
                <td><x-adm.pill :status="$order->status" /></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      @if ($orders->contains(fn ($o) => $o->isPayOnDelivery() && $o->status->isOpen()))
        <p class="muted" style="font-size:.8rem;margin-top:.6rem">Pour les commandes « à la livraison », préparez le montant en espèces ou mobile money à la réception.</p>
      @endif
      @if ($orders->contains(fn ($o) => $o->payment_mode->value === 'direct' && $o->status->isOpen()))
        <p class="muted" style="font-size:.8rem;margin-top:.6rem">Pour les demandes aux producteurs, le vendeur vous contacte pour convenir du prix et de la livraison.</p>
      @endif
    </div>
  @endif

  @if ($payments->isEmpty() && $orders->isEmpty() && ! $isProducer && ! $isBuyer)
    <div class="card"><div class="empty">
      <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/><path d="M2 3h3l2.5 13h11L21 7H6"/></svg>
      <p>Aucune commande pour l'instant.</p>
    </div></div>
  @endif

  {{-- Commandes producteur structurées (§1-§10) — reçues en tant que producteur. --}}
  @if ($isProducer)
    <div class="card">
      <div class="card-head"><h3>Commandes reçues (producteur)</h3></div>
      <div class="tabs">
        <button class="{{ $producerOrderFilter === 'nouvelles' ? 'on' : '' }}" wire:click="$set('producerOrderFilter', 'nouvelles')">Nouvelles demandes</button>
        <button class="{{ $producerOrderFilter === 'negociation' ? 'on' : '' }}" wire:click="$set('producerOrderFilter', 'negociation')">En négociation</button>
        <button class="{{ $producerOrderFilter === 'confirmees' ? 'on' : '' }}" wire:click="$set('producerOrderFilter', 'confirmees')">Confirmées</button>
        <button class="{{ $producerOrderFilter === 'livraison' ? 'on' : '' }}" wire:click="$set('producerOrderFilter', 'livraison')">En livraison</button>
        <button class="{{ $producerOrderFilter === 'terminees' ? 'on' : '' }}" wire:click="$set('producerOrderFilter', 'terminees')">Terminées</button>
        <button class="{{ $producerOrderFilter === 'refusees' ? 'on' : '' }}" wire:click="$set('producerOrderFilter', 'refusees')">Refusées / annulées</button>
      </div>
      <x-adm.loading-note target="producerOrderFilter" />
      <div class="table-wrap" style="border:none">
        <table>
          <thead><tr><th>Produit</th><th>Acheteur</th><th>Quantité</th><th>Statut</th><th></th></tr></thead>
          <tbody>
            @forelse ($producerOrders as $o)
              <tr class="row" wire:key="prod-crop-order-{{ $o->id }}">
                <td>{{ $o->product_name }}</td>
                <td class="muted">{{ $o->buyerProfile->company_name ?: 'Acheteur' }}</td>
                <td class="muted">{{ rtrim(rtrim(number_format((float) $o->requested_quantity, 2, '.', ' '), '0'), '.') }} {{ $o->requested_unit }}</td>
                <td><x-adm.pill :status="$o->status" /></td>
                <td>
                  <a class="iact" href="{{ route('learner.crop-orders.show', $o) }}" wire:navigate title="Voir la commande">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
                  </a>
                </td>
              </tr>
            @empty
              <tr><td colspan="5"><div class="empty"><p>Aucune commande pour l'instant.</p></div></td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      {{ $producerOrders->links() }}
    </div>
  @endif

  {{-- Commandes producteur structurées — passées en tant qu'acheteur. --}}
  @if ($isBuyer)
    <div class="card">
      <div class="card-head"><h3>Commandes producteurs (acheteur)</h3></div>
      <div class="tabs">
        <button class="{{ $buyerOrderFilter === 'tous' ? 'on' : '' }}" wire:click="$set('buyerOrderFilter', 'tous')">Toutes</button>
        @foreach ($buyerOrderStatuses as $s)
          <button class="{{ $buyerOrderFilter === $s->value ? 'on' : '' }}" wire:click="$set('buyerOrderFilter', '{{ $s->value }}')">{{ $s->label() }}</button>
        @endforeach
      </div>
      <x-adm.loading-note target="buyerOrderFilter" />
      <div class="table-wrap" style="border:none">
        <table>
          <thead><tr><th>Produit</th><th>Producteur</th><th>Quantité</th><th>Statut</th><th></th></tr></thead>
          <tbody>
            @forelse ($buyerOrders as $o)
              <tr class="row" wire:key="buy-crop-order-{{ $o->id }}">
                <td>{{ $o->product_name }}</td>
                <td class="muted">{{ $o->producerProfile->business_name }}</td>
                <td class="muted">{{ rtrim(rtrim(number_format((float) $o->requested_quantity, 2, '.', ' '), '0'), '.') }} {{ $o->requested_unit }}</td>
                <td><x-adm.pill :status="$o->status" /></td>
                <td>
                  <a class="iact" href="{{ route('learner.crop-orders.show', $o) }}" wire:navigate title="Voir la commande">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
                  </a>
                </td>
              </tr>
            @empty
              <tr><td colspan="5"><div class="empty"><p>Aucune commande pour l'instant.</p></div></td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      {{ $buyerOrders->links() }}
    </div>
  @endif

</div>

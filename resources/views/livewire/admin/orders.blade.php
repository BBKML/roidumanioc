@php $money = fn ($n) => number_format((int) $n, 0, ',', ' ').' FCFA'; @endphp
<div style="display:flex;flex-direction:column;gap:1.4rem">

  <div class="page-intro">
    <div>
      <h2>Commandes</h2>
      <p>Produits de la boutique. Les commandes <b>payées en ligne</b> passent en « à préparer » après confirmation du paiement (<a class="link-btn" href="{{ route('admin.payments') }}" wire:navigate>Paiements à vérifier</a>). Les commandes <b>à la livraison</b> arrivent directement en « à préparer » — l'argent s'encaisse à la remise.</p>
    </div>
  </div>

  <div class="tabs">
    <button class="{{ $filter === 'a_traiter' ? 'on' : '' }}" wire:click="setFilter('a_traiter')">À traiter ({{ $counts['a_traiter'] }})</button>
    <button class="{{ $filter === 'expediee' ? 'on' : '' }}" wire:click="setFilter('expediee')">En livraison ({{ $counts['expediee'] }})</button>
    <button class="{{ $filter === 'livree' ? 'on' : '' }}" wire:click="setFilter('livree')">Livrées</button>
    <button class="{{ $filter === 'annulee' ? 'on' : '' }}" wire:click="setFilter('annulee')">Annulées</button>
    <button class="{{ $filter === 'tous' ? 'on' : '' }}" wire:click="setFilter('tous')">Toutes</button>
  </div>
  <x-adm.loading-note target="setFilter" />

  <div class="table-wrap stack-mobile">
    <table>
      <thead><tr>
        <x-adm.sortable-th field="reference" :sort="$sort" :direction="$direction">Réf.</x-adm.sortable-th>
        <th>Client</th>
        <th>Article</th>
        <th>Paiement</th>
        <x-adm.sortable-th field="amount" :sort="$sort" :direction="$direction">Total</x-adm.sortable-th>
        <th>Statut</th>
        <th>Action</th>
      </tr></thead>
      <tbody>
        @forelse ($orders as $order)
          <tr class="row" wire:key="order-{{ $order->id }}">
            <td class="card-title" style="font-weight:800;font-family:'Fraunces',serif">
              <button class="link-btn" wire:click="toggle({{ $order->id }})">{{ $order->reference }}</button>
            </td>
            <td style="font-weight:700" data-label="Client">{{ $order->user?->name ?? $order->customer_name }}<br><span class="muted" style="font-weight:500;font-size:.78rem">{{ $order->contact_phone }}</span></td>
            <td class="muted" data-label="Article">{{ $order->item_label }}</td>
            <td data-label="Paiement">
              @php $m = $order->payment_mode->value; @endphp
              <span class="pill {{ $m === 'on_delivery' ? 'warn' : ($m === 'direct' ? 'neutral' : 'info') }}"><span class="dot"></span>{{ $order->payment_mode->label() }}</span>
              @if ($m === 'direct')<br><span class="muted" style="font-size:.72rem">annonce producteur</span>@endif
            </td>
            <td class="nums" data-label="Total">{{ $order->total() > 0 ? $money($order->total()) : 'à convenir' }}</td>
            <td data-label="Statut"><x-adm.pill :status="$order->status" /></td>
            <td class="card-actions">
              <div class="row-actions">
                @switch($order->status->value)
                  @case('paiement')
                    <a class="btn sm ghost" href="{{ route('admin.payments') }}" wire:navigate>Voir le paiement</a>
                    @break
                  @case('validee')
                    <button class="btn sm" wire:click="ship({{ $order->id }})">{{ $order->payment_mode->value === 'direct' ? 'Marquer en cours' : 'Marquer expédiée' }}</button>
                    @break
                  @case('expediee')
                    <button class="btn sm" wire:click="deliver({{ $order->id }})"
                            data-confirm="{{ $order->isPayOnDelivery() ? 'Confirmer la livraison ET l\'encaissement de '.$money($order->total()).' ?' : 'Marquer cette commande comme conclue ?' }}">
                      {{ $order->payment_mode->value === 'direct' ? 'Marquer conclue' : 'Marquer livrée' }}
                    </button>
                    @break
                  @case('livree')
                    <span class="muted">Clôturée ✓</span>
                    @break
                  @default
                    <span class="muted">—</span>
                @endswitch
                @if ($order->contact_phone && $order->status->isOpen())
                  <a class="iact" href="{{ $order->whatsappLink() }}" target="_blank" rel="noopener" title="Prévenir le client sur WhatsApp">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 5h16v11H9l-4 4V5z"/></svg>
                  </a>
                @endif
                @if ($order->status->isOpen())
                  <button class="iact danger" wire:click="startCancel({{ $order->id }})" title="Annuler">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                  </button>
                @endif
              </div>
            </td>
          </tr>
          @if ($cancelling === $order->id)
            <tr wire:key="order-cancel-{{ $order->id }}">
              <td colspan="7" style="background:var(--danger-bg)">
                <div style="display:flex;gap:.6rem;align-items:center;flex-wrap:wrap;padding:.4rem 0">
                  <b style="font-size:.85rem">Annuler {{ $order->reference }} :</b>
                  <button class="btn sm ghost" wire:click="cancel({{ $order->id }}, 'other')">Annulation simple</button>
                  <button class="btn sm ghost" wire:click="cancel({{ $order->id }}, 'unpaid')">Paiement non reçu</button>
                  <button class="btn sm danger" wire:click="cancel({{ $order->id }}, 'no_show')"
                          data-confirm="Client absent / a refusé la livraison ? Cela ajoute un avertissement à son compte.">
                    Client absent (avertissement)
                  </button>
                  <button class="link-btn" wire:click="$set('cancelling', null)">Fermer</button>
                </div>
              </td>
            </tr>
          @endif
          @if ($expanded === $order->id)
            <tr wire:key="order-exp-{{ $order->id }}">
              <td colspan="7" style="background:var(--cream);font-size:.85rem">
                <div style="display:flex;gap:2rem;flex-wrap:wrap;padding:.4rem 0">
                  <div><span class="muted">Livraison</span><br>{{ $order->delivery_address }}, {{ $order->delivery_city }}</div>
                  <div><span class="muted">Sous-total / livraison</span><br>{{ $money($order->amount) }} + {{ $order->delivery_fee ? $money($order->delivery_fee) : 'à convenir' }}</div>
                  <div><span class="muted">Commandée le</span><br>{{ $order->ordered_at?->format('d/m/Y H:i') }}</div>
                  @if ($order->handledBy)<div><span class="muted">Traitée par</span><br>{{ $order->handledBy->name }}</div>@endif
                  @if ($order->customer_note)<div style="flex-basis:100%"><span class="muted">Note client</span><br>{{ $order->customer_note }}</div>@endif
                </div>
              </td>
            </tr>
          @endif
        @empty
          <tr><td colspan="7"><div class="empty"><p>
            @switch($filter)
              @case('a_traiter') Aucune commande à traiter pour le moment. @break
              @case('expediee') Aucune commande en cours de livraison. @break
              @case('livree') Aucune commande livrée pour l'instant. @break
              @case('annulee') Aucune commande annulée. @break
              @default Aucune commande pour le moment.
            @endswitch
          </p></div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{ $orders->links() }}

</div>

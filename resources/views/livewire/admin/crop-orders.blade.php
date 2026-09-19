<div style="display:flex;flex-direction:column;gap:1.4rem">

  <div class="page-intro">
    <div>
      <h2>Commandes producteur</h2>
      <p>Supervision en lecture seule — chaque commande reste pilotée par le producteur et l'acheteur, jamais par l'admin (sauf l'aide à la livraison, gérée à part).</p>
    </div>
  </div>

  <div class="tabs">
    <button class="{{ $filter === 'tous' ? 'on' : '' }}" wire:click="$set('filter', 'tous')">Toutes</button>
    @foreach ($statuses as $s)
      <button class="{{ $filter === $s->value ? 'on' : '' }}" wire:click="$set('filter', '{{ $s->value }}')">{{ $s->label() }}</button>
    @endforeach
  </div>
  <x-adm.loading-note target="filter" />

  <div class="table-wrap stack-mobile">
    <table>
      <thead><tr><th>Produit</th><th>Producteur</th><th>Acheteur</th><th>Total</th><th>Statut</th><th></th></tr></thead>
      <tbody>
        @forelse ($orders as $o)
          <tr class="row" wire:key="admin-crop-order-{{ $o->id }}">
            <td class="card-title">{{ $o->product_name }}</td>
            <td class="muted" data-label="Producteur">{{ $o->producerProfile->business_name }}</td>
            <td class="muted" data-label="Acheteur">{{ $o->buyerProfile->company_name ?: 'Acheteur' }}</td>
            <td class="muted" data-label="Total">{{ $o->total_amount ? number_format($o->total_amount, 0, ',', ' ').' FCFA' : 'À convenir' }}</td>
            <td data-label="Statut"><x-adm.pill :status="$o->status" /></td>
            <td class="card-actions">
              <a class="iact" href="{{ route('learner.crop-orders.show', $o) }}" wire:navigate title="Voir la commande">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
              </a>
            </td>
          </tr>
        @empty
          <tr><td colspan="6"><div class="empty"><p>
            @if ($filter === 'tous')
              Aucune commande pour le moment.
            @else
              Aucune commande au statut « {{ \App\Enums\CropOrderStatus::from($filter)->label() }} » pour le moment.
            @endif
          </p></div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{ $orders->links() }}

</div>

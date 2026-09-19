<div style="display:flex;flex-direction:column;gap:1.4rem">

  <div class="page-intro">
    <div>
      <h2>Besoins acheteur</h2>
      <p>Modération : fermez un besoin problématique, rouvrez-le si besoin.</p>
    </div>
  </div>

  <div style="display:flex;gap:.8rem;flex-wrap:wrap;align-items:center">
    <label class="search" style="background:var(--paper)">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
      <input type="search" wire:model.live.debounce.300ms="search" placeholder="Produit, acheteur…">
    </label>
    <div class="tabs">
      <button class="{{ $filter === 'tous' ? 'on' : '' }}" wire:click="$set('filter', 'tous')">Tous</button>
      @foreach ($statuses as $s)
        <button class="{{ $filter === $s->value ? 'on' : '' }}" wire:click="$set('filter', '{{ $s->value }}')">{{ $s->label() }}</button>
      @endforeach
    </div>
    <x-adm.loading-note />
  </div>

  <div class="table-wrap stack-mobile">
    <table>
      <thead><tr><th>Produit recherché</th><th>Acheteur</th><th>Quantité</th><th>Statut</th><th></th></tr></thead>
      <tbody>
        @forelse ($needs as $need)
          <tr class="row" wire:key="need-{{ $need->id }}">
            <td class="card-title">{{ $need->product_wanted }}</td>
            <td class="muted" data-label="Acheteur">{{ $need->buyerProfile->company_name ?: 'Acheteur' }}</td>
            <td class="muted" data-label="Quantité">{{ $need->quantity }} {{ $need->unit->label() }}</td>
            <td data-label="Statut"><x-adm.pill :status="$need->status" /></td>
            <td class="card-actions">
              <div class="icon-actions">
                @if ($need->status->value !== 'ferme')
                  <button class="iact danger" wire:click="close({{ $need->id }})" data-confirm="Fermer ce besoin ?" title="Fermer">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                  </button>
                @else
                  <button class="iact primary" wire:click="reopen({{ $need->id }})" data-confirm="Rouvrir ce besoin ?" title="Rouvrir">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7M3 4v5h5"/></svg>
                  </button>
                @endif
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="5"><div class="empty">
            @if ($search !== '')
              <p>Aucun besoin ne correspond à cette recherche.</p>
              <button type="button" class="link-btn" wire:click="resetFilters">Réinitialiser la recherche</button>
            @elseif ($filter === 'tous')
              <p>Aucun besoin publié par un acheteur pour le moment.</p>
            @else
              <p>Aucun besoin au statut « {{ \App\Enums\BuyerNeedStatus::from($filter)->label() }} » pour le moment.</p>
            @endif
          </div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{ $needs->links() }}

</div>

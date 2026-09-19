<div style="display:flex;flex-direction:column;gap:1.4rem">

  <div class="page-intro">
    <div>
      <h2>Acheteurs</h2>
      <p>{{ $stats['total'] }} acheteur(s).</p>
    </div>
  </div>

  <div style="display:flex;gap:.8rem;flex-wrap:wrap;align-items:center">
    <label class="search" style="background:var(--paper)">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
      <input type="search" wire:model.live.debounce.300ms="search" placeholder="Entreprise, zone…">
    </label>
    <div class="tabs">
      <button class="{{ $filter === 'tous' ? 'on' : '' }}" wire:click="$set('filter', 'tous')">Tous</button>
      <button class="{{ $filter === 'suspendus' ? 'on' : '' }}" wire:click="$set('filter', 'suspendus')">Suspendus</button>
    </div>
    <x-adm.loading-note />
  </div>

  <div class="table-wrap stack-mobile">
    <table>
      <thead><tr><th>Acheteur</th><th>Zone</th><th>Type</th><th>Besoins</th><th>Statut</th><th></th></tr></thead>
      <tbody>
        @forelse ($buyers as $buyer)
          <tr class="row" wire:key="bp-{{ $buyer->id }}">
            <td class="card-title">
              {{ $buyer->company_name ?: 'Acheteur' }}
              <div class="muted" style="font-size:.72rem">{{ $buyer->user->name }}</div>
            </td>
            <td class="muted" data-label="Zone">{{ $buyer->zone }}</td>
            <td class="muted" data-label="Type">{{ $buyer->buyer_type->label() }}</td>
            <td class="nums" data-label="Besoins">{{ $buyer->buyer_needs_count }}</td>
            <td data-label="Statut"><x-adm.pill :status="$buyer->user->status" /></td>
            <td class="card-actions">
              <button
                class="btn sm {{ $buyer->user->isActive() ? 'ghost is-danger' : '' }}"
                wire:click="toggleSuspend({{ $buyer->id }})"
                data-confirm="{{ $buyer->user->isActive() ? 'Suspendre ce compte ?' : 'Réactiver ce compte ?' }}"
                @disabled($buyer->user->id === auth()->id())
              >
                {{ $buyer->user->isActive() ? 'Suspendre' : 'Réactiver' }}
              </button>
            </td>
          </tr>
        @empty
          <tr><td colspan="6"><div class="empty">
            @if ($search !== '')
              <p>Aucun acheteur ne correspond à cette recherche.</p>
              <button type="button" class="link-btn" wire:click="resetFilters">Réinitialiser la recherche</button>
            @elseif ($filter === 'suspendus')
              <p>Aucun acheteur suspendu.</p>
            @else
              <p>Aucun acheteur pour le moment.</p>
            @endif
          </div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{ $buyers->links() }}

</div>

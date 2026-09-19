<div style="display:flex;flex-direction:column;gap:1.4rem">

  <div class="page-intro">
    <div>
      <h2>Offres producteur</h2>
      <p>Modération : retirez une offre problématique (archivage), restaurez-la si besoin.</p>
    </div>
  </div>

  <div style="display:flex;gap:.8rem;flex-wrap:wrap;align-items:center">
    <label class="search" style="background:var(--paper)">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
      <input type="search" wire:model.live.debounce.300ms="search" placeholder="Produit, producteur…">
    </label>
    <div class="tabs">
      <button class="{{ $filter === 'tous' ? 'on' : '' }}" wire:click="$set('filter', 'tous')">Toutes</button>
      @foreach ($statuses as $s)
        <button class="{{ $filter === $s->value ? 'on' : '' }}" wire:click="$set('filter', '{{ $s->value }}')">{{ $s->label() }}</button>
      @endforeach
    </div>
    <x-adm.loading-note />
  </div>

  <div class="table-wrap stack-mobile">
    <table>
      <thead><tr><th>Produit</th><th>Producteur</th><th>Quantité</th><th>Statut</th><th></th></tr></thead>
      <tbody>
        @forelse ($offers as $offer)
          <tr class="row" wire:key="offer-{{ $offer->id }}">
            <td class="card-title">{{ $offer->product_name }}</td>
            <td class="muted" data-label="Producteur">{{ $offer->producerProfile->business_name }}</td>
            <td class="muted" data-label="Quantité">{{ $offer->quantity }} {{ $offer->unit->label() }}</td>
            <td data-label="Statut"><x-adm.pill :status="$offer->status" /></td>
            <td class="card-actions">
              <div class="icon-actions">
                @if ($offer->status->value !== 'archivee')
                  <button class="iact danger" wire:click="archive({{ $offer->id }})" data-confirm="Archiver cette offre ?" title="Archiver">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 8h18M5 8v11a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8M10 12h4"/></svg>
                  </button>
                @else
                  <button class="iact primary" wire:click="restore({{ $offer->id }})" data-confirm="Republier cette offre ?" title="Republier">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 3-6.7M3 4v5h5"/></svg>
                  </button>
                @endif
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="5"><div class="empty">
            @if ($search !== '')
              <p>Aucune offre ne correspond à cette recherche.</p>
              <button type="button" class="link-btn" wire:click="resetFilters">Réinitialiser la recherche</button>
            @elseif ($filter === 'tous')
              <p>Aucune offre publiée par un producteur pour le moment.</p>
            @else
              <p>Aucune offre au statut « {{ \App\Enums\CropOfferStatus::from($filter)->label() }} » pour le moment.</p>
            @endif
          </div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{ $offers->links() }}

</div>

<div style="display:flex;flex-direction:column;gap:1.4rem">

  <div class="page-intro">
    <div>
      <h2>Producteurs</h2>
      <p>{{ $stats['total'] }} producteur(s) · {{ $stats['verifies'] }} vérifié(s). Le badge « Producteur vérifié ✅ » est affiché publiquement — vérifiez l'identité avant de l'accorder.</p>
    </div>
  </div>

  <div style="display:flex;gap:.8rem;flex-wrap:wrap;align-items:center">
    <label class="search" style="background:var(--paper)">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
      <input type="search" wire:model.live.debounce.300ms="search" placeholder="Nom, zone…">
    </label>
    <div class="tabs">
      <button class="{{ $filter === 'a_verifier' ? 'on' : '' }}" wire:click="$set('filter', 'a_verifier')">À vérifier</button>
      <button class="{{ $filter === 'verifies' ? 'on' : '' }}" wire:click="$set('filter', 'verifies')">Vérifiés</button>
      <button class="{{ $filter === 'tous' ? 'on' : '' }}" wire:click="$set('filter', 'tous')">Tous</button>
    </div>
    <x-adm.loading-note />
  </div>

  <div class="table-wrap stack-mobile">
    <table>
      <thead><tr>
        <x-adm.sortable-th field="business_name" :sort="$sort" :direction="$direction">Producteur</x-adm.sortable-th>
        <x-adm.sortable-th field="zone" :sort="$sort" :direction="$direction">Zone</x-adm.sortable-th>
        <th>Activité</th>
        <x-adm.sortable-th field="crop_offers_count" :sort="$sort" :direction="$direction">Offres</x-adm.sortable-th>
        <th>Statut</th>
        <th></th>
      </tr></thead>
      <tbody>
        @forelse ($profiles as $profile)
          <tr class="row" wire:key="pp-{{ $profile->id }}">
            <td class="card-title">
              <a href="{{ route('admin.producers.show', $profile) }}" wire:navigate>{{ $profile->business_name }}</a>
              <div class="muted" style="font-size:.72rem">{{ $profile->user->name }}</div>
            </td>
            <td class="muted" data-label="Zone">{{ $profile->zone }}</td>
            <td class="muted" data-label="Activité">{{ $profile->activity_type->label() }}</td>
            <td class="nums" data-label="Offres">{{ $profile->crop_offers_count }}</td>
            <td data-label="Statut">
              @if ($profile->isVerified())
                <span class="pill ok"><span class="dot"></span>Vérifié</span>
              @else
                <span class="pill warn"><span class="dot"></span>À vérifier</span>
              @endif
            </td>
            <td class="card-actions">
              <div class="icon-actions">
                <a class="iact" href="{{ route('admin.producers.show', $profile) }}" wire:navigate title="Voir la fiche">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
                </a>
                @if (! $profile->isVerified())
                  <button class="iact primary" wire:click="verify({{ $profile->id }})" data-confirm="Accorder le badge « Producteur vérifié » ?" title="Vérifier">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                  </button>
                @else
                  <button class="iact danger" wire:click="reject({{ $profile->id }})" data-confirm="Retirer le badge « Producteur vérifié » ?" title="Retirer la vérification">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                  </button>
                @endif
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="6"><div class="empty">
            @if ($search !== '')
              <p>Aucun producteur ne correspond à cette recherche.</p>
              <button type="button" class="link-btn" wire:click="resetFilters">Réinitialiser la recherche</button>
            @elseif ($filter === 'verifies')
              <p>Aucun producteur vérifié pour le moment.</p>
            @elseif ($filter === 'a_verifier')
              <p>Aucun producteur en attente de vérification — tout est à jour !</p>
            @else
              <p>Aucun producteur pour le moment.</p>
            @endif
          </div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{ $profiles->links() }}

</div>

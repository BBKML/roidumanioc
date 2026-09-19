<div style="display:flex;flex-direction:column;gap:1.4rem">

  <div class="page-intro">
    <div>
      <h2>Marketplace</h2>
      <p>Modérez les annonces des producteurs avant leur publication sur la vitrine.</p>
    </div>
  </div>

  <div class="tabs">
    <button class="{{ $filter === 'en_attente' ? 'on' : '' }}" wire:click="setFilter('en_attente')">À modérer ({{ $counts['en_attente'] }})</button>
    <button class="{{ $filter === 'validee' ? 'on' : '' }}" wire:click="setFilter('validee')">Publiées ({{ $counts['validee'] }})</button>
    <button class="{{ $filter === 'refuse' ? 'on' : '' }}" wire:click="setFilter('refuse')">Refusées ({{ $counts['refuse'] }})</button>
    <button class="{{ $filter === 'tous' ? 'on' : '' }}" wire:click="setFilter('tous')">Toutes</button>
  </div>

  <div class="table-wrap stack-mobile">
    <table>
      <thead><tr><th>Annonce</th><th>Type</th><th>Lieu</th><th>Prix</th><th>Vendeur</th><th>Statut</th><th></th></tr></thead>
      <tbody>
        @forelse ($listings as $listing)
          <tr class="row" wire:key="listing-{{ $listing->id }}">
            <td class="card-title">
              <div class="cell-main">
                @if ($listing->image_path)<img src="{{ asset($listing->image_path) }}" alt="">@endif
                {{ $listing->title }}
                @if ($listing->is_official)<span class="badge-prem">Officiel</span>@endif
              </div>
            </td>
            <td class="muted" data-label="Type">{{ $listing->type }}</td>
            <td class="muted" data-label="Lieu">{{ $listing->location }}</td>
            <td class="nums" data-label="Prix">{{ $listing->price_label }}</td>
            <td data-label="Vendeur">{{ $listing->seller?->name ?? $listing->seller_name ?? '—' }}</td>
            <td data-label="Statut"><x-adm.pill :status="$listing->status" /></td>
            <td class="card-actions">
              <div class="icon-actions">
                @if ($listing->status->value === 'en_attente')
                  <button class="iact primary" wire:click="approve({{ $listing->id }})"
                          data-confirm="Publier cette annonce sur la vitrine publique ?" title="Publier l'annonce">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M20 6 9 17l-5-5"/></svg>
                  </button>
                  <button class="iact danger" wire:click="reject({{ $listing->id }})"
                          data-confirm="Refuser cette annonce ?" title="Refuser">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                  </button>
                @elseif ($listing->status->value === 'validee')
                  <button class="iact" wire:click="unpublish({{ $listing->id }})"
                          data-confirm="Retirer cette annonce de la vitrine publique ?" title="Retirer de la vitrine">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 3l18 18"/><path d="M10.6 10.6a3 3 0 0 0 4.2 4.2"/><path d="M9.9 4.24A9.1 9.1 0 0 1 12 4c6 0 10 8 10 8a17.6 17.6 0 0 1-3.06 3.86M6.1 6.1A17.5 17.5 0 0 0 2 12s4 8 10 8a9 9 0 0 0 4-.94"/></svg>
                  </button>
                @else
                  <button class="iact primary" wire:click="approve({{ $listing->id }})"
                          data-confirm="Publier cette annonce sur la vitrine publique ?" title="Publier quand même">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M20 6 9 17l-5-5"/></svg>
                  </button>
                @endif
                <button class="iact danger" wire:click="delete({{ $listing->id }})" data-confirm="Supprimer définitivement cette annonce ?" title="Supprimer">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M9 7V5h6v2M6 7l1 13h10l1-13"/></svg>
                </button>
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="7"><div class="empty"><p>
            @switch($filter)
              @case('en_attente') Aucune annonce en attente de modération. @break
              @case('validee') Aucune annonce publiée pour le moment. @break
              @case('refuse') Aucune annonce refusée. @break
              @default Aucune annonce pour le moment.
            @endswitch
          </p></div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{ $listings->links() }}

</div>

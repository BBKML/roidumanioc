<div style="display:flex;flex-direction:column;gap:1.4rem">

  <div class="page-intro">
    <div>
      <h2>Demandes de mise en relation</h2>
      <p>Supervision en lecture seule — chaque demande reste pilotée par les deux parties, jamais par l'admin.</p>
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
      <thead><tr><th>Objet</th><th>Producteur</th><th>Acheteur</th><th>Statut</th><th></th></tr></thead>
      <tbody>
        @forelse ($requests as $r)
          <tr class="row" wire:key="req-{{ $r->id }}">
            <td class="card-title">{{ $r->cropOffer->product_name ?? $r->buyerNeed?->product_wanted }}</td>
            <td class="muted" data-label="Producteur">{{ $r->producerProfile->business_name }}</td>
            <td class="muted" data-label="Acheteur">{{ $r->buyerProfile->company_name ?: 'Acheteur' }}</td>
            <td data-label="Statut"><x-adm.pill :status="$r->status" /></td>
            <td class="card-actions">
              <a class="iact" href="{{ route('learner.requests.show', $r) }}" wire:navigate title="Voir la demande">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
              </a>
            </td>
          </tr>
        @empty
          <tr><td colspan="5"><div class="empty"><p>
            @if ($filter === 'tous')
              Aucune demande de mise en relation pour le moment.
            @else
              Aucune demande au statut « {{ \App\Enums\ConnectionRequestStatus::from($filter)->label() }} » pour le moment.
            @endif
          </p></div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{ $requests->links() }}

</div>

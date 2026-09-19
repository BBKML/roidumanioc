<div style="display:flex;flex-direction:column;gap:1.4rem">

  <div class="page-intro">
    <div>
      <h2>Mes demandes</h2>
      <p>Les demandes de mise en relation reçues ou envoyées à propos de vos besoins.</p>
    </div>
  </div>

  <div class="tabs">
    <button class="{{ $filter === 'tous' ? 'on' : '' }}" wire:click="$set('filter', 'tous')">Toutes</button>
    @foreach ($statuses as $s)
      <button class="{{ $filter === $s->value ? 'on' : '' }}" wire:click="$set('filter', '{{ $s->value }}')">{{ $s->label() }}</button>
    @endforeach
  </div>
  <x-adm.loading-note target="filter" />

  <div class="table-wrap">
    <table>
      <thead><tr><th>Objet</th><th>Producteur</th><th>Statut</th><th></th></tr></thead>
      <tbody>
        @forelse ($requests as $r)
          <tr class="row" wire:key="req-{{ $r->id }}">
            <td>{{ $r->buyerNeed->product_wanted ?? $r->cropOffer?->product_name }}</td>
            <td class="muted">{{ $r->producerProfile->business_name }}</td>
            <td><x-adm.pill :status="$r->status" /></td>
            <td>
              <a class="iact" href="{{ route('learner.requests.show', $r) }}" wire:navigate title="Voir la demande">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
              </a>
            </td>
          </tr>
        @empty
          <tr><td colspan="4"><div class="empty"><p>Aucune demande pour l'instant.</p></div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{ $requests->links() }}

</div>

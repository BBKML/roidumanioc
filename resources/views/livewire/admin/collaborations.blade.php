<div style="display:flex;flex-direction:column;gap:1.4rem">

  <div class="page-intro">
    <div>
      <h2>Collaborations</h2>
      <p>{{ $counts['tous'] }} collaboration(s) au total · {{ $counts['litige'] }} en litige.</p>
    </div>
  </div>

  <div class="tabs">
    <button class="{{ $filter === 'litige' ? 'on' : '' }}" wire:click="$set('filter', 'litige')">Litiges ({{ $counts['litige'] }})</button>
    <button class="{{ $filter === 'tous' ? 'on' : '' }}" wire:click="$set('filter', 'tous')">Toutes</button>
    @foreach ($statuses as $s)
      @if ($s->value !== 'litige')
        <button class="{{ $filter === $s->value ? 'on' : '' }}" wire:click="$set('filter', '{{ $s->value }}')">{{ $s->label() }}</button>
      @endif
    @endforeach
  </div>
  <x-adm.loading-note target="filter" />

  <div class="table-wrap stack-mobile">
    <table>
      <thead><tr><th>Produit</th><th>Producteur</th><th>Acheteur</th><th>Valeur estimée</th><th>Statut</th><th></th></tr></thead>
      <tbody>
        @forelse ($collaborations as $c)
          <tr class="row" wire:key="collab-{{ $c->id }}">
            <td class="card-title">{{ $c->agreed_product }}</td>
            <td class="muted" data-label="Producteur">{{ $c->producerProfile->business_name }}</td>
            <td class="muted" data-label="Acheteur">{{ $c->buyerProfile->company_name ?: 'Acheteur' }}</td>
            <td class="nums" data-label="Valeur estimée">{{ $c->estimatedValue() ? number_format($c->estimatedValue(), 0, ',', ' ').' FCFA' : '—' }}</td>
            <td data-label="Statut"><x-adm.pill :status="$c->status" /></td>
            <td class="card-actions">
              <div class="icon-actions">
                <a class="iact" href="{{ route('learner.collaborations.show', $c) }}" wire:navigate title="Voir le détail">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
                </a>
                @if ($c->status->value === 'litige')
                  <button class="btn sm" wire:click="startResolving({{ $c->id }})">Résoudre</button>
                @endif
              </div>
            </td>
          </tr>
          @if ($resolvingId === $c->id)
            <tr wire:key="resolve-{{ $c->id }}">
              <td colspan="6">
                <div class="card pad-lg" style="border-color:var(--leaf)">
                  <div class="field">
                    <label>Résolution du litige — {{ $c->agreed_product }}</label>
                    <textarea wire:model="resolution" rows="3" placeholder="Ce qui a été convenu / constaté…"></textarea>
                    @error('resolution') <span class="inline-err">{{ $message }}</span> @enderror
                  </div>
                  <div style="display:flex;gap:.6rem">
                    <button class="btn" wire:click="resolveDispute" data-confirm="Résoudre ce litige et remettre la collaboration en cours ?">Confirmer la résolution</button>
                    <button type="button" class="btn ghost" wire:click="cancelResolving">Annuler</button>
                  </div>
                </div>
              </td>
            </tr>
          @endif
        @empty
          <tr><td colspan="6"><div class="empty"><p>
            @switch($filter)
              @case('litige') Aucun litige en cours — tout se passe bien entre les parties. @break
              @case('tous') Aucune collaboration pour le moment. @break
              @default Aucune collaboration au statut « {{ \App\Enums\CollaborationStatus::from($filter)->label() }} » pour le moment.
            @endswitch
          </p></div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{ $collaborations->links() }}

</div>

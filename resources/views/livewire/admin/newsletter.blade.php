<div style="display:flex;flex-direction:column;gap:1.4rem">

  <div class="page-intro">
    <div>
      <h2>Infolettre</h2>
      <p>{{ $stats['active'] }} abonné(s) actif(s) · {{ $stats['unsubscribed'] }} désinscrit(s).
        Collecte des e-mails depuis le pied de page du site. L'envoi des campagnes se fait avec un outil externe (Brevo, Mailchimp…) — exportez la liste ici.</p>
    </div>
    <a class="btn ghost" href="{{ route('admin.newsletter.export') }}">
      <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 4v12M6 10l6 6 6-6M4 20h16"/></svg>
      Exporter (CSV)
    </a>
  </div>

  <form wire:submit="add" class="card" style="display:flex;gap:.7rem;flex-wrap:wrap;align-items:end">
    <div class="field" style="margin:0;flex:1;min-width:220px">
      <label>Ajouter un e-mail manuellement</label>
      <input type="email" wire:model="newEmail" placeholder="contact@exemple.ci">
      @error('newEmail') <span class="inline-err">{{ $message }}</span> @enderror
    </div>
    <button type="submit" class="btn">Ajouter</button>
  </form>

  <div style="display:flex;gap:.8rem;flex-wrap:wrap;align-items:center">
    <label class="search" style="background:var(--paper)">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
      <input type="search" wire:model.live.debounce.300ms="search" placeholder="Rechercher un e-mail…">
    </label>
    <div class="tabs">
      <button class="{{ $filter === 'actifs' ? 'on' : '' }}" wire:click="setFilter('actifs')">Actifs ({{ $stats['active'] }})</button>
      <button class="{{ $filter === 'desinscrits' ? 'on' : '' }}" wire:click="setFilter('desinscrits')">Désinscrits ({{ $stats['unsubscribed'] }})</button>
      <button class="{{ $filter === 'tous' ? 'on' : '' }}" wire:click="setFilter('tous')">Tous</button>
    </div>
    <x-adm.loading-note target="search,setFilter" />
  </div>

  <div class="table-wrap stack-mobile">
    <table>
      <thead><tr><th>E-mail</th><th>Source</th><th>Inscrit le</th><th>Statut</th><th></th></tr></thead>
      <tbody>
        @forelse ($subscribers as $sub)
          <tr class="row" wire:key="sub-{{ $sub->id }}">
            <td style="font-weight:700" class="card-title">{{ $sub->email }}</td>
            <td class="muted" data-label="Source">{{ $sub->source }}</td>
            <td class="muted" data-label="Inscrit le">{{ $sub->created_at->format('d/m/Y') }}</td>
            <td data-label="Statut">
              @if ($sub->isActive())
                <span class="pill ok"><span class="dot"></span>Actif</span>
              @else
                <span class="pill neutral"><span class="dot"></span>Désinscrit</span>
              @endif
            </td>
            <td class="card-actions">
              <div class="row-actions">
                @if ($sub->isActive())
                  <button class="btn sm ghost" wire:click="unsubscribe({{ $sub->id }})">Désinscrire</button>
                @else
                  <button class="btn sm ghost" wire:click="resubscribe({{ $sub->id }})">Réinscrire</button>
                @endif
                <button class="iact danger" wire:click="delete({{ $sub->id }})" data-confirm="Supprimer cet abonné ?" title="Supprimer">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M9 7V5h6v2M6 7l1 13h10l1-13"/></svg>
                </button>
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="5"><div class="empty">
            @if ($search !== '')
              <p>Aucun abonné ne correspond à cette recherche.</p>
            @else
              <p>Aucun abonné dans cette vue.</p>
              <span class="muted" style="font-size:.78rem">Le formulaire ci-dessus permet d'en ajouter un manuellement.</span>
            @endif
          </div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{ $subscribers->links() }}

</div>

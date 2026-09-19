<div style="display:flex;flex-direction:column;gap:1.4rem">

  <div class="page-intro">
    <div>
      <h2>Avis & évaluations</h2>
      <p>{{ $counts['tous'] }} avis au total · {{ $counts['masques'] }} masqué(s). Le contenu d'un avis n'est jamais modifié ni supprimé — seul son affichage public peut être masqué.</p>
    </div>
  </div>

  <div class="tabs">
    <button class="{{ $filter === 'tous' ? 'on' : '' }}" wire:click="$set('filter', 'tous')">Tous ({{ $counts['tous'] }})</button>
    <button class="{{ $filter === 'visibles' ? 'on' : '' }}" wire:click="$set('filter', 'visibles')">Visibles</button>
    <button class="{{ $filter === 'masques' ? 'on' : '' }}" wire:click="$set('filter', 'masques')">Masqués ({{ $counts['masques'] }})</button>
  </div>
  <x-adm.loading-note target="filter" />

  <div class="table-wrap stack-mobile">
    <table>
      <thead><tr><th>Direction</th><th>Auteur → Destinataire</th><th>Note</th><th>Commentaire</th><th>Statut</th><th></th></tr></thead>
      <tbody>
        @forelse ($reviews as $r)
          <tr class="row" wire:key="review-{{ $r->id }}">
            <td data-label="Direction" class="muted">{{ $r->direction->label() }}</td>
            <td data-label="Auteur → Destinataire">{{ $r->rater->name }} → {{ $r->ratee->name }}</td>
            <td class="nums" data-label="Note">{{ $r->rating }}/5</td>
            <td data-label="Commentaire" style="max-width:260px">
              {{ $r->comment ? \Illuminate\Support\Str::limit($r->comment, 90) : '—' }}
            </td>
            <td data-label="Statut">
              <x-adm.pill :status="$r->isHidden() ? 'masque' : 'visible'" />
              @if ($r->isHidden())
                <div class="muted" style="font-size:.74rem;margin-top:.2rem">{{ $r->hidden_reason }}</div>
              @endif
            </td>
            <td class="card-actions">
              <div class="icon-actions">
                @if ($r->isHidden())
                  <button class="btn sm ghost" wire:click="unhide({{ $r->id }})" data-confirm="Réafficher cet avis ?">Réafficher</button>
                @else
                  <button class="btn sm" wire:click="startHiding({{ $r->id }})">Masquer</button>
                @endif
              </div>
            </td>
          </tr>
          @if ($hidingId === $r->id)
            <tr wire:key="hide-{{ $r->id }}">
              <td colspan="6">
                <div class="card pad-lg" style="border-color:var(--leaf)">
                  <div class="field">
                    <label>Motif du masquage — avis de {{ $r->rater->name }}</label>
                    <textarea wire:model="hideReason" rows="2" placeholder="Ex. contenu injurieux, coordonnées personnelles, avis manifestement faux…"></textarea>
                    @error('hideReason') <span class="inline-err">{{ $message }}</span> @enderror
                  </div>
                  <div style="display:flex;gap:.6rem">
                    <button class="btn" wire:click="hide" data-confirm="Masquer cet avis de l'affichage public ?">Confirmer le masquage</button>
                    <button type="button" class="btn ghost" wire:click="cancelHiding">Annuler</button>
                  </div>
                </div>
              </td>
            </tr>
          @endif
        @empty
          <tr><td colspan="6"><div class="empty"><p>
            @switch($filter)
              @case('masques') Aucun avis masqué pour l'instant. @break
              @case('visibles') Aucun avis visible pour l'instant. @break
              @default Aucun avis déposé pour l'instant.
            @endswitch
          </p></div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{ $reviews->links() }}

</div>

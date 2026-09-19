<div style="display:flex;flex-direction:column;gap:1.4rem">

  <div class="page-intro">
    <div>
      <h2>Événements</h2>
      <p>Lives, ateliers et visites de terrain annoncés aux membres.</p>
    </div>
    <button class="btn" wire:click="new">
      <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
      Nouvel événement
    </button>
  </div>

  <div class="table-wrap">
    <table>
      <thead><tr><th>Événement</th><th>Date</th><th>Type</th><th>Statut</th><th></th></tr></thead>
      <tbody>
        @forelse ($events as $event)
          <tr class="row" wire:key="event-{{ $event->id }}">
            <td style="font-weight:700">{{ $event->title }}</td>
            <td class="muted">{{ $event->date_label ?: $event->starts_at?->format('d/m/Y') ?: '—' }}</td>
            <td>{{ $event->type }}</td>
            <td><x-adm.pill :status="$event->status" /></td>
            <td>
              <div class="icon-actions">
                <button class="iact" wire:click="edit({{ $event->id }})" title="Modifier">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 20h4L20 8l-4-4L4 16v4z"/></svg>
                </button>
                <button class="iact danger" wire:click="delete({{ $event->id }})" data-confirm="Supprimer cet événement ?" title="Supprimer">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M9 7V5h6v2M6 7l1 13h10l1-13"/></svg>
                </button>
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="5"><div class="empty"><p>Aucun événement pour l'instant.</p></div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <x-adm.modal :show="$showForm" :title="$editingId ? 'Modifier l\'événement' : 'Nouvel événement'">
    <form wire:submit="save" id="event-form">
      <div class="img-field">
        @if ($image)
          <img src="{{ $image->temporaryUrl() }}" alt="">
        @else
          <img src="{{ $image_path ? \Illuminate\Support\Facades\Storage::url($image_path) : '' }}" alt="" @style(['visibility:hidden' => ! $image_path]) onerror="this.style.visibility='hidden'">
        @endif
        <div class="grow">
          <input type="file" wire:model="image" accept="image/*">
          <div wire:loading wire:target="image" class="muted" style="font-size:.78rem">Téléversement…</div>
          @error('image') <span class="inline-err">{{ $message }}</span> @enderror
          @if ($image || $image_path)
            <button type="button" class="btn sm ghost" style="align-self:flex-start" wire:click="removeImage">Retirer l'image</button>
          @endif
        </div>
      </div>
      <div class="field">
        <label>Titre</label>
        <input type="text" wire:model="title">
        @error('title') <span class="inline-err">{{ $message }}</span> @enderror
      </div>
      <div class="field-row">
        <div class="field">
          <label>Date affichée</label>
          <input type="text" wire:model="date_label" placeholder="12 sept. 2026">
          @error('date_label') <span class="inline-err">{{ $message }}</span> @enderror
        </div>
        <div class="field">
          <label>Date réelle (tri)</label>
          <input type="date" wire:model="starts_at">
          @error('starts_at') <span class="inline-err">{{ $message }}</span> @enderror
        </div>
      </div>
      <div class="field-row">
        <div class="field">
          <label>Type</label>
          <input type="text" wire:model="type" placeholder="Live, Atelier, Visite">
          @error('type') <span class="inline-err">{{ $message }}</span> @enderror
        </div>
        <div class="field">
          <label>Statut</label>
          <select wire:model="status">
            @foreach ($statuses as $s)
              <option value="{{ $s->value }}">{{ $s->label() }}</option>
            @endforeach
          </select>
        </div>
      </div>
      <div class="field">
        <label>Lien (visioconférence, inscription…)</label>
        <input type="url" wire:model="link" placeholder="https://...">
        @error('link') <span class="inline-err">{{ $message }}</span> @enderror
      </div>
      <div class="field">
        <label>Description</label>
        <textarea wire:model="description" rows="3"></textarea>
        @error('description') <span class="inline-err">{{ $message }}</span> @enderror
      </div>
    </form>
    <x-slot:footer>
      <button type="button" class="btn ghost" wire:click="$set('showForm', false)">Annuler</button>
      <button type="submit" form="event-form" class="btn">Enregistrer</button>
    </x-slot:footer>
  </x-adm.modal>

</div>

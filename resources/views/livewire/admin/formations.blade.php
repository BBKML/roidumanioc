<div style="display:flex;flex-direction:column;gap:1.4rem">

  <div class="page-intro">
    <div>
      <h2>Formations</h2>
      <p>Créez, modifiez et publiez vos parcours. Le prix décide automatiquement de l'accès (0&nbsp;FCFA = gratuit).</p>
    </div>
    <button class="btn" wire:click="new">
      <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
      Nouvelle formation
    </button>
  </div>

  <div class="table-wrap stack-mobile">
    <table>
      <thead><tr><th>Formation</th><th>Catégorie</th><th>Leçons</th><th>Prix</th><th>Inscrits</th><th>Statut</th><th></th></tr></thead>
      <tbody>
        @forelse ($formations as $formation)
          <tr class="row" wire:key="formation-{{ $formation->id }}">
            <td class="card-title">
              <div class="cell-main">
                @if ($formation->image_path)<img src="{{ \Illuminate\Support\Facades\Storage::url($formation->image_path) }}" alt="">@endif
                {{ $formation->title }}
                @if ($formation->isFree())<span class="badge-free">Gratuit</span>@else<span class="badge-prem">Premium</span>@endif
              </div>
            </td>
            <td class="muted" data-label="Catégorie">{{ $formation->category }}</td>
            <td class="nums" data-label="Leçons">{{ $formation->lessons_count }}</td>
            <td class="nums" data-label="Prix">{{ $formation->price ? number_format($formation->price, 0, ',', ' ').' FCFA' : '—' }}</td>
            <td class="nums" data-label="Inscrits">{{ $formation->validated_count }}</td>
            <td data-label="Statut">
              <button wire:click="togglePublish({{ $formation->id }})" title="Basculer brouillon / publié" style="border:none;background:none;padding:0">
                <x-adm.pill :status="$formation->status" style="cursor:pointer" />
              </button>
            </td>
            <td class="card-actions">
              <div class="icon-actions">
                <a class="iact" href="{{ route('admin.lessons', $formation) }}" wire:navigate title="Gérer les leçons">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 4v16l7-2 7 2V4l-7 2-7-2z"/><path d="M11 6v14"/></svg>
                </a>
                <button class="iact" wire:click="edit({{ $formation->id }})" title="Modifier la formation">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 20h4L20 8l-4-4L4 16v4z"/></svg>
                </button>
                <button class="iact danger" wire:click="delete({{ $formation->id }})" data-confirm="Supprimer cette formation ? Les leçons seront également supprimées." title="Supprimer">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M9 7V5h6v2M6 7l1 13h10l1-13"/></svg>
                </button>
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="7"><div class="empty"><p>Aucune formation. Créez la première.</p></div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <x-adm.modal :show="$showForm" :title="$editingId ? 'Modifier la formation' : 'Nouvelle formation'">
    <form wire:submit="save" id="formation-form">
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
          <label>Catégorie</label>
          <input type="text" wire:model="category" placeholder="Culture, Transformation…">
          @error('category') <span class="inline-err">{{ $message }}</span> @enderror
        </div>
        <div class="field">
          <label>Durée affichée</label>
          <input type="text" wire:model="duration_label" placeholder="5 h 30">
          @error('duration_label') <span class="inline-err">{{ $message }}</span> @enderror
        </div>
      </div>
      <div class="field-row">
        <div class="field">
          <label>Prix (FCFA) — 0 = gratuit</label>
          <input type="number" min="0" wire:model="price">
          @error('price') <span class="inline-err">{{ $message }}</span> @enderror
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
        <label>Description</label>
        <textarea wire:model="description" rows="3"></textarea>
        @error('description') <span class="inline-err">{{ $message }}</span> @enderror
      </div>
    </form>
    <x-slot:footer>
      <button type="button" class="btn ghost" wire:click="$set('showForm', false)">Annuler</button>
      <button type="submit" form="formation-form" class="btn">Enregistrer</button>
    </x-slot:footer>
  </x-adm.modal>

</div>

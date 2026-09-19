<div style="display:flex;flex-direction:column;gap:1.4rem">

  <div class="page-intro">
    <div>
      <h2>{{ $need ? 'Modifier le besoin' : 'Nouveau besoin' }}</h2>
      <p>Vos coordonnées ne sont jamais affichées — les producteurs répondent via la plateforme.</p>
    </div>
    <a class="btn ghost" href="{{ route('learner.buyer.needs') }}" wire:navigate>← Mes besoins</a>
  </div>

  <form wire:submit="save" class="card pad-lg" style="max-width:680px">

    <div class="field">
      <label>Produit recherché</label>
      <input type="text" wire:model="product_wanted" placeholder="Ex. Manioc frais">
      @error('product_wanted') <span class="inline-err">{{ $message }}</span> @enderror
    </div>

    <div class="field-row">
      <div class="field">
        <label>Quantité</label>
        <input type="number" step="0.01" min="0.01" wire:model="quantity">
        @error('quantity') <span class="inline-err">{{ $message }}</span> @enderror
      </div>
      <div class="field">
        <label>Unité</label>
        <select wire:model="unit">
          @foreach ($units as $u)
            <option value="{{ $u->value }}">{{ $u->label() }}</option>
          @endforeach
        </select>
        @error('unit') <span class="inline-err">{{ $message }}</span> @enderror
      </div>
    </div>

    <div class="field-row">
      <div class="field">
        <label>Zone</label>
        <input type="text" wire:model="location" placeholder="Ex. Abidjan, Cocody">
        @error('location') <span class="inline-err">{{ $message }}</span> @enderror
      </div>
      <div class="field">
        <label>Fréquence</label>
        <select wire:model="frequency">
          <option value="ponctuel">Ponctuel</option>
          <option value="recurrent">Récurrent</option>
        </select>
        @error('frequency') <span class="inline-err">{{ $message }}</span> @enderror
      </div>
    </div>

    <div class="field-row">
      <div class="field">
        <label>Date souhaitée <span class="muted">(facultatif)</span></label>
        <input type="date" wire:model="wanted_date">
        @error('wanted_date') <span class="inline-err">{{ $message }}</span> @enderror
      </div>
      <div class="field">
        <label>Budget indicatif (FCFA) <span class="muted">(facultatif)</span></label>
        <input type="number" min="1" wire:model="budget_indicative" placeholder="Laisser vide = à convenir">
        @error('budget_indicative') <span class="inline-err">{{ $message }}</span> @enderror
      </div>
    </div>

    <div class="field">
      <label>Qualité recherchée <span class="muted">(facultatif)</span></label>
      <input type="text" wire:model="quality_desc" placeholder="Ex. calibre moyen, fraîchement récolté">
      @error('quality_desc') <span class="inline-err">{{ $message }}</span> @enderror
    </div>

    <div class="field">
      <label>Statut</label>
      <select wire:model="status">
        @foreach ($statuses as $s)
          <option value="{{ $s->value }}">{{ $s->label() }}</option>
        @endforeach
      </select>
      @error('status') <span class="inline-err">{{ $message }}</span> @enderror
    </div>

    <div class="field">
      <label>Description <span class="muted">(facultatif)</span></label>
      <textarea wire:model="description" rows="4"></textarea>
      @error('description') <span class="inline-err">{{ $message }}</span> @enderror
    </div>

    <button type="submit" class="btn" wire:loading.attr="disabled" wire:target="save">
      {{ $need ? 'Enregistrer' : 'Publier le besoin' }}
    </button>
  </form>

</div>

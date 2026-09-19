<div style="display:flex;flex-direction:column;gap:1.4rem">

  <div class="page-intro">
    <div>
      <h2>{{ $offer ? 'Modifier l\'offre' : 'Nouvelle offre' }}</h2>
      <p>Visible uniquement par vous tant que le statut n'est pas « Publiée ».</p>
    </div>
    <a class="btn ghost" href="{{ route('learner.producer.offers') }}" wire:navigate>← Mes offres</a>
  </div>

  <form wire:submit="save" class="card pad-lg" style="max-width:680px">

    <div class="field">
      <label>Produit</label>
      <input type="text" wire:model="product_name" placeholder="Ex. Manioc frais">
      @error('product_name') <span class="inline-err">{{ $message }}</span> @enderror
    </div>

    <div class="field">
      <label>Variété <span class="muted">(facultatif)</span></label>
      <input type="text" wire:model="variety">
      @error('variety') <span class="inline-err">{{ $message }}</span> @enderror
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
        <label>Prix indicatif (FCFA) <span class="muted">(facultatif)</span></label>
        <input type="number" min="1" wire:model="price_indicative" placeholder="Laisser vide = à convenir">
        @error('price_indicative') <span class="inline-err">{{ $message }}</span> @enderror
      </div>
      <div class="field">
        <label>Zone</label>
        <input type="text" wire:model="location" placeholder="Ex. Daloa, Haut-Sassandra">
        @error('location') <span class="inline-err">{{ $message }}</span> @enderror
      </div>
    </div>

    <div class="field-row">
      <div class="field">
        <label>Disponible à partir du <span class="muted">(facultatif)</span></label>
        <input type="date" wire:model="available_from">
        @error('available_from') <span class="inline-err">{{ $message }}</span> @enderror
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
    </div>

    <label class="switch-row">
      <input type="checkbox" wire:model="is_available"> Disponible immédiatement
    </label>

    <div class="field">
      <label>Description <span class="muted">(facultatif)</span></label>
      <textarea wire:model="description" rows="4"></textarea>
      @error('description') <span class="inline-err">{{ $message }}</span> @enderror
    </div>

    <div class="field">
      <label>Photos <span class="muted">(jusqu'à {{ \App\Livewire\Producer\OfferForm::MAX_PHOTOS }})</span></label>

      @if ($existingPhotos->isNotEmpty())
        <div style="display:flex;flex-wrap:wrap;gap:.6rem;margin-bottom:.7rem">
          @foreach ($existingPhotos as $photo)
            <div style="position:relative" wire:key="photo-{{ $photo->id }}">
              <img src="{{ \Illuminate\Support\Facades\Storage::url($photo->path) }}" alt=""
                   style="width:88px;height:88px;object-fit:cover;border-radius:8px">
              <button type="button" wire:click="removeExistingPhoto({{ $photo->id }})" data-confirm="Retirer cette photo ?"
                      class="iact danger" style="position:absolute;top:-8px;right:-8px;width:24px;height:24px;background:var(--paper)">
                &times;
              </button>
            </div>
          @endforeach
        </div>
      @endif

      @if ($existingPhotos->count() < \App\Livewire\Producer\OfferForm::MAX_PHOTOS)
        <input type="file" wire:model="newPhotos" accept="image/*" multiple>
        <div wire:loading wire:target="newPhotos" class="muted" style="font-size:.78rem;margin-top:.3rem">Téléversement…</div>
        @error('newPhotos') <span class="inline-err">{{ $message }}</span> @enderror
        @error('newPhotos.*') <span class="inline-err">{{ $message }}</span> @enderror

        @if ($newPhotos)
          <div style="display:flex;flex-wrap:wrap;gap:.6rem;margin-top:.7rem">
            @foreach ($newPhotos as $photo)
              <img src="{{ $photo->temporaryUrl() }}" alt="" style="width:88px;height:88px;object-fit:cover;border-radius:8px">
            @endforeach
          </div>
        @endif
      @endif
    </div>

    <button type="submit" class="btn" wire:loading.attr="disabled" wire:target="save,newPhotos">
      {{ $offer ? 'Enregistrer' : 'Créer l\'offre' }}
    </button>
  </form>

</div>

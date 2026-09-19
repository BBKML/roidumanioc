<div style="display:flex;flex-direction:column;gap:1.4rem">

  <div class="page-intro">
    <div>
      <h2>Boutique officielle</h2>
      <p>Boutures, engrais et produits de traitement vendus par Le Roi du Manioc. Affichés sur la vitrine et la marketplace.</p>
    </div>
    <button class="btn" wire:click="new">
      <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
      Nouveau produit
    </button>
  </div>

  <div class="table-wrap">
    <table>
      <thead><tr><th>Produit</th><th>Catégorie</th><th>Prix</th><th>Stock</th><th>Visible</th><th></th></tr></thead>
      <tbody>
        @forelse ($products as $product)
          <tr class="row" wire:key="product-{{ $product->id }}">
            <td>
              <div class="cell-main">
                @if ($product->image_path)<img src="{{ \Illuminate\Support\Facades\Storage::url($product->image_path) }}" alt="">@endif
                {{ $product->name }}
              </div>
            </td>
            <td class="muted">{{ $product->category }}</td>
            <td class="nums">{{ number_format($product->price, 0, ',', ' ') }} FCFA</td>
            <td>
              @if ($product->isLowStock())
                <span class="pill warn"><span class="dot"></span>{{ $product->stock }} — bas</span>
              @else
                <span class="nums">{{ $product->stock }}</span>
              @endif
            </td>
            <td>
              <button class="iact {{ $product->is_active ? 'primary' : '' }}" wire:click="toggleActive({{ $product->id }})"
                      title="{{ $product->is_active ? 'Masquer' : 'Rendre visible' }}">
                @if ($product->is_active)
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
                @else
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 3l18 18"/><path d="M10.6 10.6a3 3 0 0 0 4.2 4.2"/><path d="M9.9 4.24A9.1 9.1 0 0 1 12 4c6 0 10 8 10 8a17.6 17.6 0 0 1-3.06 3.86M6.1 6.1A17.5 17.5 0 0 0 2 12s4 8 10 8a9 9 0 0 0 4-.94"/></svg>
                @endif
              </button>
            </td>
            <td>
              <div class="icon-actions">
                <button class="iact" wire:click="edit({{ $product->id }})" title="Modifier">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 20h4L20 8l-4-4L4 16v4z"/></svg>
                </button>
                <button class="iact danger" wire:click="delete({{ $product->id }})" data-confirm="Supprimer ce produit ?" title="Supprimer">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M9 7V5h6v2M6 7l1 13h10l1-13"/></svg>
                </button>
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="6"><div class="empty"><p>Aucun produit dans la boutique.</p></div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <x-adm.modal :show="$showForm" :title="$editingId ? 'Modifier le produit' : 'Nouveau produit'">
    <form wire:submit="save" id="product-form">
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
        <label>Nom du produit</label>
        <input type="text" wire:model="name">
        @error('name') <span class="inline-err">{{ $message }}</span> @enderror
      </div>
      <div class="field-row">
        <div class="field">
          <label>Catégorie</label>
          <input type="text" wire:model="category" placeholder="Bouture, Engrais, Fongicide…">
          @error('category') <span class="inline-err">{{ $message }}</span> @enderror
        </div>
        <div class="field">
          <label>Prix (FCFA)</label>
          <input type="number" min="0" wire:model="price">
          @error('price') <span class="inline-err">{{ $message }}</span> @enderror
        </div>
      </div>
      <div class="field">
        <label>Stock</label>
        <input type="number" min="0" wire:model="stock">
        @error('stock') <span class="inline-err">{{ $message }}</span> @enderror
      </div>
      <div class="field">
        <label>Description</label>
        <textarea wire:model="description" rows="3"></textarea>
        @error('description') <span class="inline-err">{{ $message }}</span> @enderror
      </div>
      <label class="switch-row">
        <input type="checkbox" wire:model="is_active"> Visible sur le site
      </label>
    </form>
    <x-slot:footer>
      <button type="button" class="btn ghost" wire:click="$set('showForm', false)">Annuler</button>
      <button type="submit" form="product-form" class="btn">Enregistrer</button>
    </x-slot:footer>
  </x-adm.modal>

</div>

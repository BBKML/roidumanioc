<div style="display:flex;flex-direction:column;gap:1.4rem">

  <div class="page-intro">
    <div>
      <a class="link-btn" href="{{ route('learner.marketplace') }}" wire:navigate>← Retour à la marketplace</a>
      <h2>Commander — {{ $listing->title }}</h2>
      <p>Cette annonce est publiée par un producteur. Le Roi du Manioc vous met en relation — le prix, la quantité et le règlement se conviennent <b>directement avec le vendeur</b>.</p>
    </div>
  </div>

  <div class="pay-grid">
    <form wire:submit="placeOrder" class="card pad-lg">
      <div class="field">
        <label>Quantité souhaitée</label>
        <input type="text" wire:model="quantityText" placeholder="Ex : 500 kg, 2 sacs, 300 unités…">
        <span class="hint">Prix affiché par le vendeur : {{ $listing->price_label }}</span>
        @error('quantityText') <span class="inline-err">{{ $message }}</span> @enderror
      </div>

      <h3 style="font-size:1rem;margin:.6rem 0 .5rem">Où livrer ?</h3>
      <div class="field-row">
        <div class="field">
          <label>Téléphone</label>
          <input type="text" wire:model="contactPhone" placeholder="07 00 00 00 00">
          @error('contactPhone') <span class="inline-err">{{ $message }}</span> @enderror
        </div>
        <div class="field">
          <label>Ville</label>
          <input type="text" wire:model="deliveryCity" placeholder="Ex : Daloa">
          @error('deliveryCity') <span class="inline-err">{{ $message }}</span> @enderror
        </div>
      </div>
      <div class="field">
        <label>Adresse / point de repère</label>
        <input type="text" wire:model="deliveryAddress" placeholder="Quartier, rue, repère…">
        @error('deliveryAddress') <span class="inline-err">{{ $message }}</span> @enderror
      </div>
      <div class="field">
        <label>Message au vendeur (facultatif)</label>
        <textarea wire:model="message" rows="2" placeholder="Précisez votre besoin, vos disponibilités…"></textarea>
        @error('message') <span class="inline-err">{{ $message }}</span> @enderror
      </div>

      <div class="pay-panel" style="margin:.4rem 0 1rem">
        <p style="font-size:.85rem;margin:0"><b>Aucun paiement en ligne ici.</b> Votre demande est transmise au producteur, qui vous appelle pour finaliser. Vous payez ensuite <b>directement</b> (mobile money ou à la livraison).</p>
      </div>

      <div class="hero-actions" style="display:flex;gap:.6rem;flex-wrap:wrap">
        <button type="submit" class="btn" wire:loading.attr="disabled" wire:target="placeOrder">Envoyer ma demande</button>
        @if ($this->sellerWhatsapp())
          <a class="btn wa" href="{{ $this->sellerWhatsapp() }}" target="_blank" rel="noopener">
            <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 5h16v11H9l-4 4V5z"/></svg>
            Discuter sur WhatsApp
          </a>
        @endif
      </div>
    </form>

    <div class="card pad-lg pay-summary">
      <div class="card-head"><h3>L'annonce</h3></div>
      @if ($listing->image_path)
        <img src="{{ asset($listing->image_path) }}" alt="" style="width:100%;border-radius:10px;margin-bottom:.8rem">
      @endif
      <div class="line"><span>Type</span><span>{{ $listing->type }}</span></div>
      <div class="line"><span>Lieu</span><span>{{ $listing->location }}</span></div>
      <div class="line"><span>Vendeur</span><span>{{ $listing->seller?->name ?? $listing->seller_name }}</span></div>
      <div class="line total"><span>Prix</span><span>{{ $listing->price_label }}</span></div>
    </div>
  </div>

</div>

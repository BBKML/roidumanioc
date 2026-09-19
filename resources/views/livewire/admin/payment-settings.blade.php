<div style="display:flex;flex-direction:column;gap:1.4rem">

  <div class="page-intro">
    <div>
      <h2>Paramètres</h2>
      <p>Comptes de paiement affichés aux clients sur la page de paiement. Le nom, les textes et les images du site se gèrent dans <b>Contenu du site</b>.</p>
    </div>
  </div>

  <form wire:submit="save" class="card pad-lg">
    <div class="card-head">
      <h3>Comptes de paiement</h3>
      <span class="muted">visibles par le client au moment de payer</span>
    </div>

    <div class="field-row">
      <div class="field">
        <label>Numéro WhatsApp (réception des preuves)</label>
        <input type="text" wire:model="whatsapp" placeholder="2250700000000">
        <span class="hint">format international sans « + » ni espaces</span>
        @error('whatsapp') <span class="inline-err">{{ $message }}</span> @enderror
      </div>
      <div class="field">
        <label>Wave — n° marchand</label>
        <input type="text" wire:model="wave">
        @error('wave') <span class="inline-err">{{ $message }}</span> @enderror
      </div>
    </div>

    <div class="field-row">
      <div class="field">
        <label>Orange Money — n° marchand</label>
        <input type="text" wire:model="orange">
        @error('orange') <span class="inline-err">{{ $message }}</span> @enderror
      </div>
      <div class="field">
        <label>MTN MoMo — n° marchand</label>
        <input type="text" wire:model="mtn">
        @error('mtn') <span class="inline-err">{{ $message }}</span> @enderror
      </div>
    </div>

    <div class="field-row">
      <div class="field">
        <label>Moov Money — n° marchand</label>
        <input type="text" wire:model="moov">
        @error('moov') <span class="inline-err">{{ $message }}</span> @enderror
      </div>
      <div class="field">
        <label>Banque</label>
        <input type="text" wire:model="bank_name">
        @error('bank_name') <span class="inline-err">{{ $message }}</span> @enderror
      </div>
    </div>

    <div class="field">
      <label>RIB / IBAN</label>
      <input type="text" wire:model="rib">
      @error('rib') <span class="inline-err">{{ $message }}</span> @enderror
    </div>

    <div class="field">
      <label>Lien de paiement carte / international (Flutterwave, PayPal…)</label>
      <input type="url" wire:model="intl_link" placeholder="https://...">
      @error('intl_link') <span class="inline-err">{{ $message }}</span> @enderror
    </div>

    <div class="card-head" style="margin-top:1.4rem"><h3>Livraison des produits</h3></div>
    <div class="field-row">
      <div class="field">
        <label>Frais de livraison (FCFA)</label>
        <input type="number" min="0" wire:model="delivery_fee" placeholder="0 = à convenir avec le client">
        @error('delivery_fee') <span class="inline-err">{{ $message }}</span> @enderror
      </div>
      <div class="field">
        <label>Note de livraison (affichée au client)</label>
        <input type="text" wire:model="delivery_note" placeholder="Ex : Livraison Abidjan sous 48 h, autres villes à convenir.">
        @error('delivery_note') <span class="inline-err">{{ $message }}</span> @enderror
      </div>
    </div>

    <button type="submit" class="btn">
      <span wire:loading.remove wire:target="save">Enregistrer les comptes</span>
      <span wire:loading wire:target="save">Enregistrement…</span>
    </button>
  </form>

</div>

{{-- Champs de déclaration de paiement — à placer dans un <form> du composant parent. --}}
<div style="border-top:1px solid var(--sand);margin-top:1.3rem;padding-top:1.2rem">
  <h3 style="font-size:1rem;margin-bottom:.2rem">Confirmez votre paiement</h3>
  <p class="muted" style="font-size:.8rem;margin-bottom:1rem">Ajoutez la capture du reçu et le montant envoyé — un contrôle automatique compare avec le prix attendu. L'équipe confirme après avoir vu l'argent sur le compte marchand.</p>

  <div class="field">
    <label>Capture du reçu (obligatoire)</label>
    <div class="upload">
      <label class="file">
        <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 16V4M6 10l6-6 6 6M4 20h16"/></svg>
        {{ $proof ? 'Changer la capture' : 'Choisir une image' }}
        <input type="file" wire:model="proof" accept="image/*">
      </label>
      <div wire:loading wire:target="proof" class="muted" style="font-size:.78rem">Téléversement…</div>
      @if ($proof)
        <img src="{{ $proof->temporaryUrl() }}" alt="aperçu">
        <span class="chk">✔ capture ajoutée</span>
      @endif
    </div>
    @error('proof') <span class="inline-err">{{ $message }}</span> @enderror
  </div>

  <div class="field-row">
    <div class="field">
      <label>Montant envoyé (FCFA)</label>
      <input type="number" min="1" wire:model="declaredAmount">
      @error('declaredAmount') <span class="inline-err">{{ $message }}</span> @enderror
    </div>
    <div class="field">
      <label>N° de transaction (sur le reçu)</label>
      <input type="text" wire:model="transactionId" placeholder="Ex : TX480021573">
      @error('transactionId') <span class="inline-err">{{ $message }}</span> @enderror
    </div>
  </div>
</div>

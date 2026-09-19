<div style="display:flex;flex-direction:column;gap:1.4rem">

  <div class="page-intro">
    <div>
      <h2>Répondre à ce besoin</h2>
      <p>La mise en relation se fait via la plateforme — vos coordonnées personnelles ne sont jamais partagées automatiquement.</p>
    </div>
  </div>

  <div class="card pad-lg" style="max-width:640px">
    <div class="card-head"><h3>{{ $need->product_wanted }}</h3></div>
    <p style="font-size:.88rem;color:var(--ink-soft)">
      {{ $need->location }}
      · {{ number_format((float) $need->quantity, 2, ',', ' ') }} {{ $need->unit->label() }}
      @if ($need->budget_indicative)
        · {{ number_format($need->budget_indicative, 0, ',', ' ') }} FCFA
      @endif
    </p>

    <form wire:submit="send" style="margin-top:1rem">
      <p class="muted" style="font-size:.78rem;margin:0 0 .8rem">
        Pré-rempli avec les valeurs du besoin — ajustez si vous négociez, ou videz ces
        champs pour simplement discuter d'abord.
      </p>
      <div class="field-row">
        <div class="field">
          <label>Quantité proposée <span class="muted">({{ $need->unit->label() }})</span></label>
          <input type="number" step="0.01" min="0.01" wire:model="quantity">
          @error('quantity') <span class="inline-err">{{ $message }}</span> @enderror
        </div>
        <div class="field">
          <label>Prix proposé (FCFA) <span class="muted">(facultatif)</span></label>
          <input type="number" min="1" wire:model="priceTotal">
          @error('priceTotal') <span class="inline-err">{{ $message }}</span> @enderror
        </div>
      </div>
      <div class="field">
        <label>Votre message <span class="muted">(facultatif)</span></label>
        <textarea wire:model="message" rows="4" placeholder="Présentez votre offre, vos disponibilités…"></textarea>
        @error('message') <span class="inline-err">{{ $message }}</span> @enderror
      </div>
      {{-- Erreur générale de l'envoi (doublon, trop de demandes…) : jamais sous le champ
           message, qui n'y est pour rien — affichée juste au-dessus du bouton d'action. --}}
      @error('general')
        <div style="background:var(--danger-bg);color:var(--danger);font-weight:700;font-size:.83rem;padding:.65rem .85rem;border-radius:8px;margin-bottom:.9rem">{{ $message }}</div>
      @enderror
      <button type="submit" class="btn" wire:loading.attr="disabled">Envoyer la réponse</button>
    </form>
  </div>

</div>

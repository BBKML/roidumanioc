<div style="display:flex;flex-direction:column;gap:1.4rem">

  <div class="page-intro">
    <div>
      <h2>Passer une commande</h2>
      <p>Décrivez votre besoin — le producteur pourra l'accepter, la refuser, ou vous proposer des conditions de livraison à négocier.</p>
    </div>
  </div>

  <div class="card pad-lg" style="max-width:680px">
    <div class="card-head"><h3>{{ $offer->product_name }}</h3></div>
    <p style="font-size:.88rem;color:var(--ink-soft)">
      {{ $offer->producerProfile->business_name }} · {{ $offer->location }}
      · {{ number_format((float) $offer->quantity, 2, ',', ' ') }} {{ $offer->unit->label() }} disponible
      @if ($offer->price_indicative)
        · à partir de {{ number_format($offer->price_indicative, 0, ',', ' ') }} FCFA
      @endif
    </p>

    <form wire:submit="send" style="margin-top:1rem">

      <p class="muted" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.06em;font-weight:800;margin:0 0 .6rem">Produit</p>
      <div class="field-row">
        <div class="field">
          <label>Quantité souhaitée</label>
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

      <p class="muted" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.06em;font-weight:800;margin:1.2rem 0 .6rem">Livraison</p>
      <div class="field">
        <label>Lieu de livraison</label>
        <input type="text" wire:model="deliveryLocation">
        @error('deliveryLocation') <span class="inline-err">{{ $message }}</span> @enderror
      </div>
      <div class="field">
        <label>Date souhaitée <span class="muted">(facultatif)</span></label>
        <input type="date" wire:model="desiredDate">
        @error('desiredDate') <span class="inline-err">{{ $message }}</span> @enderror
      </div>
      <div class="field">
        <label>Informations complémentaires sur la livraison <span class="muted">(facultatif)</span></label>
        <textarea wire:model="deliveryNotes" rows="2"></textarea>
        @error('deliveryNotes') <span class="inline-err">{{ $message }}</span> @enderror
      </div>

      <p class="muted" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.06em;font-weight:800;margin:1.2rem 0 .6rem">Paiement</p>
      <div class="field">
        <label>Moyen de paiement souhaité</label>
        <select wire:model.live="paymentMethod">
          <option value="">Sélectionner…</option>
          @foreach (\App\Livewire\Buyer\CropOrderForm::PAYMENT_METHODS as $method)
            <option value="{{ $method }}">{{ $method }}</option>
          @endforeach
        </select>
        @error('paymentMethod') <span class="inline-err">{{ $message }}</span> @enderror
      </div>
      @if ($paymentMethod === 'Autre')
        <div class="field">
          <label>Précisez</label>
          <input type="text" wire:model="paymentMethodOther">
          @error('paymentMethodOther') <span class="inline-err">{{ $message }}</span> @enderror
        </div>
      @endif

      <p class="muted" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.06em;font-weight:800;margin:1.2rem 0 .6rem">Informations supplémentaires</p>
      <div class="field">
        <label>Qualité recherchée <span class="muted">(facultatif)</span></label>
        <input type="text" wire:model="qualityExpected" placeholder="Ex. bien mûr, calibre moyen…">
        @error('qualityExpected') <span class="inline-err">{{ $message }}</span> @enderror
      </div>
      <div class="field">
        <label>Observations / message au producteur <span class="muted">(facultatif)</span></label>
        <textarea wire:model="buyerMessage" rows="3"></textarea>
        @error('buyerMessage') <span class="inline-err">{{ $message }}</span> @enderror
      </div>

      @error('general')
        <div style="background:var(--danger-bg);color:var(--danger);font-weight:700;font-size:.83rem;padding:.65rem .85rem;border-radius:8px;margin-bottom:.9rem">{{ $message }}</div>
      @enderror

      <button type="submit" class="btn" wire:loading.attr="disabled">Envoyer la commande</button>
    </form>
  </div>

</div>

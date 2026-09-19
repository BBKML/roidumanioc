@php $money = fn ($n) => number_format((int) $n, 0, ',', ' ').' FCFA'; @endphp
<div style="display:flex;flex-direction:column;gap:1.4rem">

  <div class="page-intro">
    <div>
      <a class="link-btn" href="{{ route('learner.marketplace') }}" wire:navigate>← Retour à la marketplace</a>
      <h2>Commander — {{ $product->name }}</h2>
      <p>Choisissez de payer maintenant ou à la livraison. Renseignez où livrer.</p>
    </div>
  </div>

  <form wire:submit="placeOrder">
    <div class="pay-grid">
      <div class="card pad-lg">

        {{-- Mode de paiement --}}
        <h3 style="font-size:1rem;margin-bottom:.5rem">Comment souhaitez-vous payer ?</h3>
        @if ($codAllowed)
          <div class="tabs" style="margin-bottom:1rem">
            <button type="button" class="{{ $mode === 'online' ? 'on' : '' }}" wire:click="$set('mode', 'online')">Payer maintenant</button>
            <button type="button" class="{{ $mode === 'on_delivery' ? 'on' : '' }}" wire:click="$set('mode', 'on_delivery')">Payer à la livraison</button>
          </div>
        @else
          <p class="muted" style="font-size:.82rem;margin-bottom:1rem">{{ $codBlockedReason }}</p>
        @endif
        @error('mode') <span class="inline-err">{{ $message }}</span> @enderror

        {{-- Coordonnées de livraison (toujours) --}}
        <div style="border-top:1px solid var(--sand);padding-top:1rem">
          <h3 style="font-size:1rem;margin-bottom:.6rem">Livraison</h3>
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
            <label>Note (facultatif)</label>
            <textarea wire:model="customerNote" rows="2" placeholder="Précisions pour la livraison…"></textarea>
            @error('customerNote') <span class="inline-err">{{ $message }}</span> @enderror
          </div>
          @if ($settings->delivery_note)
            <p class="muted" style="font-size:.78rem">{{ $settings->delivery_note }}</p>
          @endif
        </div>

        {{-- Paiement en ligne (si mode = online) --}}
        @if ($mode === 'online')
          <div style="border-top:1px solid var(--sand);margin-top:1rem;padding-top:1rem">
            @include('livewire.learner.partials.pay-instructions', [
              'amountExpected' => $this->total(),
              'itemLabel' => $product->name.($quantity > 1 ? ' ×'.$quantity : ''),
            ])
            @include('livewire.learner.partials.pay-fields')
          </div>
        @else
          <div class="pay-panel" style="margin-top:1rem">
            <p style="font-size:.86rem;margin:0">Vous réglerez <b>{{ $money($this->total()) }}</b> en espèces ou mobile money <b>à la réception</b> de votre commande. Notre équipe vous appelle pour convenir de la livraison.</p>
          </div>
        @endif

        <button type="submit" class="btn" style="margin-top:1.3rem;width:100%;justify-content:center"
                wire:loading.attr="disabled" wire:target="placeOrder,proof">
          {{ $mode === 'online' ? "J'ai payé — envoyer la preuve" : 'Confirmer la commande' }}
        </button>
      </div>

      <div class="card pad-lg pay-summary">
        <div class="card-head"><h3>Récapitulatif</h3></div>
        <div class="line"><span>{{ $product->name }}</span><span class="nums">{{ $money($product->price) }}</span></div>
        <div class="line">
          <span>Quantité</span>
          <span class="qty">
            <button type="button" wire:click="setQty({{ $quantity - 1 }})">−</button>
            <span>{{ $quantity }}</span>
            <button type="button" wire:click="setQty({{ $quantity + 1 }})">+</button>
          </span>
        </div>
        <div class="line"><span>Sous-total</span><span class="nums">{{ $money($this->subtotal()) }}</span></div>
        <div class="line"><span>Livraison</span><span class="nums">{{ $this->deliveryFee() ? $money($this->deliveryFee()) : 'à convenir' }}</span></div>
        <div class="line total"><span>{{ $mode === 'online' ? 'À payer' : 'À payer à la livraison' }}</span><span>{{ $money($this->total()) }}</span></div>
        @error('quantity') <span class="inline-err">{{ $message }}</span> @enderror
        <p class="muted" style="font-size:.78rem;margin-top:.8rem">Stock disponible : {{ $product->stock }}.</p>
      </div>
    </div>
  </form>

</div>

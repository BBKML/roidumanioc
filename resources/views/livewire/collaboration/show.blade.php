@php
    $c = $collaboration;
@endphp
<div style="display:flex;flex-direction:column;gap:1.4rem">

  <div class="page-intro">
    <div>
      <h2>{{ $c->agreed_product }}</h2>
      <p>
        Producteur : <b>{{ $c->producerProfile->business_name }}</b>
        · Acheteur : <b>{{ $c->buyerProfile->company_name ?: 'Acheteur' }}</b>
      </p>
    </div>
    <x-adm.pill :status="$c->status" />
  </div>

  @if ($isViewerAdmin)
    <div class="card pad-lg" style="border:1px dashed var(--gold);display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap">
      <p style="margin:0;font-size:.84rem">
        <b>Vue administrateur — lecture seule.</b> Vous consultez cette collaboration en tant qu'administrateur, sans être producteur ni acheteur ici. Vous pouvez uniquement signaler un litige.
      </p>
      <a class="btn sm ghost" href="{{ route('admin.collaborations') }}" wire:navigate>← Retour aux collaborations</a>
    </div>
  @endif

  @if ($c->status->value === 'litige')
    <div class="card pad-lg" style="border-color:var(--danger)">
      <p style="margin:0;font-weight:700;color:var(--danger)">Cette collaboration fait l'objet d'un litige signalé par l'équipe.</p>
    </div>
  @elseif ($c->status->value === 'annulee')
    <div class="card pad-lg" style="border-color:var(--danger)">
      <p style="margin:0;font-weight:700;color:var(--danger)">Cette collaboration a été annulée.</p>
    </div>
  @endif

  <div class="card pad-lg">
    <div class="card-head"><h3>Progression</h3></div>
    <div class="stepper">
      @foreach ($steps as $i => $step)
        @php
          $state = $currentIndex !== null && $i <= $currentIndex && $i === $currentIndex
              ? 'is-current'
              : ($currentIndex !== null && $i <= $currentIndex ? 'is-done' : 'upcoming');
        @endphp
        <div class="step {{ $state }}">
          <div class="step-dot">{{ $state === 'is-done' ? '✓' : $i + 1 }}</div>
          <div class="step-line"></div>
          <div class="step-body">
            <b>{{ $step->label() }}</b>
            @if ($state === 'is-current')<span>Étape actuelle</span>@endif
          </div>
        </div>
      @endforeach
    </div>
  </div>

  <div class="grid g-3">

    {{-- Bloc 1 : Accord — résumé de commande façon document plutôt qu'un simple
         paragraphe (imprimable : c'est l'écran qui fait foi une fois la collaboration
         confirmée). --}}
    <div class="card pad-lg">
      <x-adm.order-summary
        :product="$c->agreed_product"
        :quantity="$c->agreed_quantity"
        :unit="\App\Enums\CropUnit::from($c->agreed_unit)->label()"
        :price-total="$c->agreed_price_total"
        :producer-name="$c->producerProfile->business_name"
        :buyer-name="$c->buyerProfile->company_name ?: 'Acheteur'"
        :status="$c->status"
        :date="$c->started_at"
        :printable="true"
      />
      @if ($c->terms_note)
        <p class="muted" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.06em;font-weight:800;margin-top:1rem">Ce qui a été convenu</p>
        <p style="font-size:.86rem;white-space:pre-line">{{ $c->terms_note }}</p>
      @endif

      @if ($canCancel)
        <button class="btn ghost is-danger" style="margin-top:1rem" wire:click="cancel" data-confirm="Annuler cette collaboration ?">Annuler</button>
      @endif

      @if ($isViewerAdmin && $canDispute)
        <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--sand)">
          @if (! $showDisputeForm)
            <button type="button" class="btn ghost is-danger" wire:click="$set('showDisputeForm', true)">Signaler un litige</button>
          @else
            <div class="field">
              <label>Motif du litige</label>
              <textarea wire:model="disputeReason" rows="3"></textarea>
              @error('disputeReason') <span class="inline-err">{{ $message }}</span> @enderror
            </div>
            <button class="btn is-danger" wire:click="markDisputed">Confirmer le litige</button>
            <button type="button" class="btn ghost" wire:click="$set('showDisputeForm', false)">Annuler</button>
          @endif
        </div>
      @endif
    </div>

    {{-- Bloc 2 : Paiement --}}
    <div class="card pad-lg">
      <div class="card-head"><h3>Paiement</h3></div>

      @if ($latestPayment)
        <p style="font-size:.86rem;margin:0 0 .3rem">
          {{ number_format($latestPayment->amount_declared, 0, ',', ' ') }} FCFA · {{ $latestPayment->method }}
        </p>
        <x-adm.pill :status="$latestPayment->status" />
        @if ($latestPayment->note)
          <p class="muted" style="font-size:.82rem;margin-top:.6rem;white-space:pre-line">{{ $latestPayment->note }}</p>
        @endif
      @else
        <p class="muted" style="font-size:.84rem">Aucun paiement déclaré pour l'instant.</p>
      @endif

      @if ($canDeclarePayment)
        <form wire:submit="declarePayment" style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--sand)">
          <div class="field">
            <label>Montant payé (FCFA)</label>
            <input type="number" min="1" wire:model="amountDeclared">
            @error('amountDeclared') <span class="inline-err">{{ $message }}</span> @enderror
          </div>
          <div class="field">
            <label>Moyen de paiement</label>
            <input type="text" wire:model="method" placeholder="Ex. Mobile Money, espèces, virement">
            @error('method') <span class="inline-err">{{ $message }}</span> @enderror
          </div>
          <div class="field">
            <label>Précisions <span class="muted">(facultatif)</span></label>
            <textarea wire:model="paymentNote" rows="2" placeholder="Référence de transaction, contexte…"></textarea>
            @error('paymentNote') <span class="inline-err">{{ $message }}</span> @enderror
          </div>
          <p class="muted" style="font-size:.76rem">
            Cette déclaration ne constitue pas une preuve bancaire automatique — c'est au producteur de vérifier la réception réelle avant de confirmer.
          </p>
          <button type="submit" class="btn">Déclarer le paiement</button>
        </form>
      @endif

      @if ($canRespondToPayment)
        <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--sand)">
          <p class="muted" style="font-size:.76rem;font-weight:700">
            ⚠️ Cette confirmation ne constitue pas une preuve bancaire automatique. Vérifiez la réception réelle des fonds avant de confirmer.
          </p>
          <div style="display:flex;gap:.6rem;flex-wrap:wrap;margin-top:.6rem">
            <button class="btn" wire:click="confirmPayment" data-confirm="Confirmez-vous avoir bien reçu ce paiement ?">J'ai vérifié, confirmer</button>
            @if (! $showContestForm)
              <button type="button" class="btn ghost is-danger" wire:click="$set('showContestForm', true)">Contester</button>
            @endif
          </div>
          @if ($showContestForm)
            <div class="field" style="margin-top:.8rem">
              <label>Pourquoi contestez-vous cette déclaration ?</label>
              <textarea wire:model="contestReason" rows="3"></textarea>
              @error('contestReason') <span class="inline-err">{{ $message }}</span> @enderror
            </div>
            <button class="btn ghost is-danger" wire:click="contestPayment">Confirmer la contestation</button>
            <button type="button" class="btn ghost" wire:click="$set('showContestForm', false)">Annuler</button>
          @endif
        </div>
      @endif
    </div>

    {{-- Bloc 3 : Livraison --}}
    <div class="card pad-lg">
      <div class="card-head"><h3>Livraison</h3></div>

      @php $deliveryStatus = $c->delivery?->status; @endphp
      <div class="stepper">
        @foreach ($deliverySteps as $i => $step)
          @php
            $dIndex = $deliveryStatus ? array_search($deliveryStatus, $deliverySteps, true) : 0;
            $dState = $i < $dIndex ? 'is-done' : ($i === $dIndex ? 'is-current' : 'upcoming');
          @endphp
          <div class="step {{ $dState }}">
            <div class="step-dot">{{ $dState === 'is-done' ? '✓' : $i + 1 }}</div>
            <div class="step-line"></div>
            <div class="step-body"><b>{{ $step->label() }}</b></div>
          </div>
        @endforeach
      </div>

      <div style="display:flex;gap:.6rem;flex-wrap:wrap;margin-top:1rem">
        @if ($canMarkEnCours)
          <button class="btn" wire:click="markDeliveryStep('en_cours')">Marquer « en cours »</button>
        @endif
        @if ($canMarkEffectuee)
          <button class="btn" wire:click="markDeliveryStep('effectuee')">Marquer « effectuée »</button>
        @endif
        @if ($canMarkReceptionnee)
          <button class="btn" wire:click="markDeliveryStep('receptionnee')" data-confirm="Confirmez-vous avoir bien reçu la marchandise ?">J'ai reçu, confirmer</button>
        @endif
      </div>
    </div>

  </div>

  @if ($c->status->value === 'terminee')
    <livewire:connect.review-form :collaboration="$c" :key="'review-'.$c->id" />
  @endif

</div>

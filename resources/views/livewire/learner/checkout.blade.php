@php $money = fn ($n) => number_format((int) $n, 0, ',', ' ').' FCFA'; @endphp
<div style="display:flex;flex-direction:column;gap:1.4rem">

  <div class="page-intro">
    <div>
      <a class="link-btn" href="{{ route('learner.catalog') }}" wire:navigate>← Retour au catalogue</a>
      <h2>Finaliser le paiement</h2>
      <p>Réglez par mobile money, virement ou carte. Votre accès à la formation s'active dès vérification du paiement par l'équipe.</p>
    </div>
  </div>

  <div class="pay-grid">
    <div class="card pad-lg">
      @include('livewire.learner.partials.pay-instructions', [
        'amountExpected' => $formation->price,
        'itemLabel' => 'Formation — '.$formation->title,
      ])
      <form wire:submit="declarePayment">
        @include('livewire.learner.partials.pay-fields')
        <button type="submit" class="btn wa" style="margin-top:1.2rem" wire:loading.attr="disabled" wire:target="declarePayment,proof">
          <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 5h16v11H9l-4 4V5z"/></svg>
          J'ai payé — envoyer la preuve
        </button>
        <p class="muted" style="font-size:.75rem;margin-top:.8rem">Aucun débit automatique. Votre inscription passe en « paiement à vérifier ».</p>
      </form>
    </div>

    <div class="card pad-lg pay-summary">
      <div class="card-head"><h3>Récapitulatif</h3></div>
      <div class="line"><span>{{ $formation->title }}</span><span class="nums">{{ $money($formation->price) }}</span></div>
      <div class="line total"><span>À payer</span><span>{{ $money($formation->price) }}</span></div>
      <p class="muted" style="font-size:.78rem;margin-top:.8rem">{{ $formation->lessons()->count() }} leçons · accès à vie une fois le paiement confirmé.</p>
    </div>
  </div>

</div>

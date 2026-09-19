@php
    $steps = \App\Enums\DeliveryAssistStatus::steps();
@endphp
<div style="display:flex;flex-direction:column;gap:1.4rem">

  <div class="page-intro">
    <div>
      <h2>🚚 Aide livraison</h2>
      <p>Organisez la livraison des commandes confirmées dont une partie a demandé l'aide de l'administration.</p>
    </div>
  </div>

  <div class="tabs">
    <button class="{{ $filter === 'en_cours' ? 'on' : '' }}" wire:click="$set('filter', 'en_cours')">En cours</button>
    @foreach ($statuses as $s)
      <button class="{{ $filter === $s->value ? 'on' : '' }}" wire:click="$set('filter', '{{ $s->value }}')">{{ $s->label() }}</button>
    @endforeach
    <button class="{{ $filter === 'tous' ? 'on' : '' }}" wire:click="$set('filter', 'tous')">Toutes</button>
  </div>
  <x-adm.loading-note target="filter" />

  <div style="display:flex;flex-direction:column;gap:1rem">
    @forelse ($orders as $o)
      @php
        $assist = $o->deliveryAssist;
        $currentIndex = array_search($assist->status, $steps, true);
        $nextStep = $currentIndex !== false && $currentIndex < count($steps) - 1 ? $steps[$currentIndex + 1] : null;
        $canCancel = ! in_array($assist->status, [\App\Enums\DeliveryAssistStatus::Livree, \App\Enums\DeliveryAssistStatus::Annulee], true);
      @endphp
      <div class="card pad-lg" wire:key="assist-{{ $o->id }}">
        <div class="card-head" style="justify-content:space-between">
          <h3>Commande #{{ $o->id }} — {{ $o->product_name }}</h3>
          <x-adm.pill :status="$assist->status" />
        </div>

        <dl class="kv order-summary-kv">
          <dt>Producteur</dt>
          <dd>{{ $o->producerProfile->business_name }}</dd>
          <dt>Acheteur</dt>
          <dd>{{ $o->buyerProfile->company_name ?: 'Acheteur' }}</dd>
          <dt>Quantité</dt>
          <dd>{{ rtrim(rtrim(number_format((float) $o->requested_quantity, 2, '.', ' '), '0'), '.') }} {{ $o->requested_unit }}</dd>
          <dt>Prix du produit</dt>
          <dd>{{ number_format((float) $o->product_price_total, 0, ',', ' ') }} FCFA</dd>
          <dt>Frais de livraison convenus</dt>
          <dd>{{ number_format((float) $o->delivery_fee_agreed, 0, ',', ' ') }} FCFA</dd>
          <dt>Total</dt>
          <dd><b>{{ number_format((float) $o->total_amount, 0, ',', ' ') }} FCFA</b></dd>
          <dt>Lieu de départ</dt>
          <dd>{{ $o->producerProfile->zone }}</dd>
          <dt>Lieu de livraison</dt>
          <dd>{{ $o->delivery_location }}</dd>
          <dt>Date souhaitée</dt>
          <dd>{{ $o->desired_date?->translatedFormat('d F Y') ?: 'Non précisée' }}</dd>
          <dt>Moyen de paiement</dt>
          <dd>{{ $o->payment_method }}</dd>
          @if ($o->delivery_notes)
            <dt>Infos complémentaires</dt>
            <dd>{{ $o->delivery_notes }}</dd>
          @endif
        </dl>

        <div style="display:flex;gap:.6rem;flex-wrap:wrap;margin-top:1rem;padding-top:1rem;border-top:1px solid var(--sand)">
          @if ($nextStep)
            <button class="btn sm" wire:click="markStep({{ $o->id }}, '{{ $nextStep->value }}')">
              Marquer « {{ $nextStep->label() }} »
            </button>
          @endif
          @if ($canCancel)
            <button class="btn sm ghost is-danger" wire:click="markStep({{ $o->id }}, 'annulee')" data-confirm="Annuler cette aide à la livraison ?">Annuler l'aide</button>
          @endif
          <a class="btn sm ghost" href="{{ route('learner.crop-orders.show', $o) }}" wire:navigate>Voir la commande</a>
        </div>
      </div>
    @empty
      <div class="empty"><p>Aucune demande d'aide à la livraison pour le moment.</p></div>
    @endforelse
  </div>

  {{ $orders->links() }}

</div>

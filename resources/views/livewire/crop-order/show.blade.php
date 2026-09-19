@php
    $o = $cropOrder;
    $unitLabel = \App\Enums\CropUnit::tryFrom($o->requested_unit)?->label() ?? $o->requested_unit;
    $isTerminalNegative = in_array($o->status, [\App\Enums\CropOrderStatus::Refusee, \App\Enums\CropOrderStatus::Annulee], true);
@endphp
<div style="display:flex;flex-direction:column;gap:1.4rem">

  <div class="page-intro">
    <div>
      <h2>{{ $o->product_name }}</h2>
      <p>
        Producteur : <b>{{ $o->producerProfile->business_name }}</b>
        · Acheteur : <b>{{ $o->buyerProfile->company_name ?: 'Acheteur' }}</b>
      </p>
    </div>
    <x-adm.pill :status="$o->status" />
  </div>

  @if ($isViewerAdmin)
    <div class="card pad-lg" style="border:1px dashed var(--gold);display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap">
      <p style="margin:0;font-size:.84rem">
        <b>Vue administrateur — lecture seule.</b> Vous consultez cette commande en tant qu'administrateur, sans être ni le producteur ni l'acheteur concerné.
      </p>
      <a class="btn sm ghost" href="{{ route('admin.crop-orders') }}" wire:navigate>← Retour aux commandes</a>
    </div>
  @endif

  @if ($isTerminalNegative)
    <div class="card pad-lg" style="border-color:var(--danger)">
      <p style="margin:0;font-weight:700;color:var(--danger)">
        Cette commande est {{ $o->status === \App\Enums\CropOrderStatus::Annulee ? 'annulée' : 'refusée' }}.
        @if ($o->status === \App\Enums\CropOrderStatus::Refusee && $o->refusal_reason)
          Motif : {{ $o->refusal_reason }}
        @endif
      </p>
    </div>
  @endif

  <div class="grid g-3">

    {{-- Bloc 1 : Progression + demande initiale --}}
    <div class="card pad-lg">
      <div class="card-head"><h3>Progression</h3></div>
      <div class="stepper">
        @foreach ($steps as $i => $step)
          @php
            $state = $furthestIndex !== null && $i <= $furthestIndex && $i === $currentIndex
                ? 'is-current'
                : ($furthestIndex !== null && $i <= $furthestIndex ? 'is-done' : 'upcoming');
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

        @if ($isTerminalNegative)
          <div class="step is-terminal">
            <div class="step-dot">✕</div>
            <div class="step-body">
              <b>{{ $o->status->label() }}</b>
              <span>{{ $o->updated_at->translatedFormat('d F Y à H\hi') }}</span>
            </div>
          </div>
        @endif
      </div>

      @if ($canAccept || $canRefuse || $canCancel)
        <div style="display:flex;gap:.6rem;flex-wrap:wrap;margin-top:1.4rem;padding-top:1.2rem;border-top:1px solid var(--sand)">
          @if ($canAccept)
            <button class="btn sm" wire:click="accept">Accepter</button>
          @endif
          @if ($canRefuse && ! $showRefuseForm)
            <button type="button" class="btn sm ghost is-danger" wire:click="$set('showRefuseForm', true)">Refuser</button>
          @endif
          @if ($canCancel)
            <button class="btn sm ghost is-danger" wire:click="cancel" data-confirm="Annuler cette commande ?">Annuler la commande</button>
          @endif
        </div>
      @endif

      @if ($canRefuse && $showRefuseForm)
        <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--sand)">
          <div class="field">
            <label>Motif du refus <span class="muted">(facultatif)</span></label>
            <textarea wire:model="refusalReason" rows="2"></textarea>
            @error('refusalReason') <span class="inline-err">{{ $message }}</span> @enderror
          </div>
          <button class="btn sm is-danger" wire:click="refuse">Confirmer le refus</button>
          <button type="button" class="btn sm ghost" wire:click="$set('showRefuseForm', false)">Annuler</button>
        </div>
      @endif
    </div>

    {{-- Bloc 2 : Détails de la demande --}}
    <div class="card pad-lg">
      <x-adm.order-summary
        :product="$o->product_name"
        :quantity="$o->requested_quantity"
        :unit="$unitLabel"
        :price-total="$o->total_amount"
        :zone="$o->delivery_location"
        :producer-name="$o->producerProfile->business_name"
        :buyer-name="$o->buyerProfile->company_name ?: 'Acheteur'"
        :date="$o->desired_date"
      />
      <dl class="kv order-summary-kv" style="margin-top:.8rem">
        <dt>Moyen de paiement</dt>
        <dd>{{ $o->payment_method }}</dd>
        @if ($o->quality_expected)
          <dt>Qualité recherchée</dt>
          <dd>{{ $o->quality_expected }}</dd>
        @endif
      </dl>
      @if ($o->buyer_message)
        <p class="muted" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.06em;font-weight:800;margin-top:1rem">Message de l'acheteur</p>
        <p style="font-size:.86rem;white-space:pre-line">{{ $o->buyer_message }}</p>
      @endif
      @if ($o->delivery_notes)
        <p class="muted" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.06em;font-weight:800;margin-top:1rem">Infos de livraison</p>
        <p style="font-size:.86rem;white-space:pre-line">{{ $o->delivery_notes }}</p>
      @endif
    </div>

    {{-- Bloc 3 : Conditions et négociation des frais de livraison --}}
    <div class="card pad-lg">
      <div class="card-head"><h3>Livraison</h3></div>

      @if ($canSubmitDeliveryConditions)
        <form wire:submit="submitDeliveryConditions">
          <p class="muted" style="font-size:.78rem">
            Renseignez le prix du produit pour cette commande et le montant de frais de livraison que vous proposez.
            Ces conditions seront d'abord soumises à l'administration, qui les valide avant qu'elles
            n'atteignent l'acheteur.
          </p>
          <div class="field-row">
            <div class="field">
              <label>Prix du produit (FCFA)</label>
              <input type="number" min="1" wire:model="productPriceTotal">
              @error('productPriceTotal') <span class="inline-err">{{ $message }}</span> @enderror
            </div>
            <div class="field">
              <label>Frais de livraison proposés (FCFA)</label>
              <input type="number" min="0" wire:model="deliveryFeeProposed">
              @error('deliveryFeeProposed') <span class="inline-err">{{ $message }}</span> @enderror
            </div>
          </div>
          <div class="field">
            <label>Précisions <span class="muted">(facultatif)</span></label>
            <textarea wire:model="deliveryConditionsNote" rows="2"></textarea>
            @error('deliveryConditionsNote') <span class="inline-err">{{ $message }}</span> @enderror
          </div>
          <button type="submit" class="btn">Soumettre à l'administration</button>
        </form>
      @endif

      @if ($o->status === \App\Enums\CropOrderStatus::EnAttenteValidationAdmin)
        @if ($isViewerAdmin && $canReviewDeliveryConditions)
          <div>
            <p class="muted" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.06em;font-weight:800">Conditions soumises par le producteur — jamais encore visibles de l'acheteur</p>
            <dl class="kv order-summary-kv">
              <dt>Prix du produit</dt>
              <dd>{{ number_format((float) $o->pending_product_price_total, 0, ',', ' ') }} FCFA</dd>
              <dt>Frais de livraison proposés</dt>
              <dd>{{ number_format((float) $o->pending_delivery_fee, 0, ',', ' ') }} FCFA</dd>
              @if ($o->delivery_conditions_note)
                <dt>Note du producteur</dt>
                <dd>{{ $o->delivery_conditions_note }}</dd>
              @endif
            </dl>
            <div style="display:flex;gap:.6rem;flex-wrap:wrap;margin-top:1rem">
              <button class="btn sm" wire:click="approveDeliveryConditions" data-confirm="Valider ces conditions et les envoyer à l'acheteur ?">Valider et envoyer à l'acheteur</button>
              @if (! $showRejectForm)
                <button type="button" class="btn sm ghost is-danger" wire:click="$set('showRejectForm', true)">Renvoyer au producteur</button>
              @endif
            </div>
            @if ($showRejectForm)
              <div style="margin-top:.8rem">
                <div class="field">
                  <label>Motif du renvoi (visible du producteur)</label>
                  <textarea wire:model="adminReviewNote" rows="2"></textarea>
                  @error('adminReviewNote') <span class="inline-err">{{ $message }}</span> @enderror
                </div>
                <button class="btn sm is-danger" wire:click="rejectDeliveryConditions">Confirmer le renvoi</button>
                <button type="button" class="btn sm ghost" wire:click="$set('showRejectForm', false)">Annuler</button>
              </div>
            @endif
          </div>
        @elseif ($o->isProducer(auth()->user()))
          <p style="margin:0;font-weight:700">En attente de validation par l'administration.</p>
          <dl class="kv order-summary-kv" style="margin-top:.6rem">
            <dt>Prix soumis</dt>
            <dd>{{ number_format((float) $o->pending_product_price_total, 0, ',', ' ') }} FCFA</dd>
            <dt>Frais de livraison soumis</dt>
            <dd>{{ number_format((float) $o->pending_delivery_fee, 0, ',', ' ') }} FCFA</dd>
          </dl>
        @else
          <p style="margin:0;font-weight:700">Le producteur a soumis ses conditions de livraison.</p>
          <p class="muted" style="font-size:.84rem">En attente de validation par l'administration avant de vous être communiquées.</p>
        @endif
      @endif

      @if (! in_array($o->status, [\App\Enums\CropOrderStatus::Acceptee, \App\Enums\CropOrderStatus::EnAttenteValidationAdmin], true) && $o->product_price_total)
        <dl class="kv order-summary-kv">
          <dt>Prix du produit</dt>
          <dd>{{ number_format((float) $o->product_price_total, 0, ',', ' ') }} FCFA</dd>
        </dl>
      @endif

      @if ($o->status === \App\Enums\CropOrderStatus::NegociationLivraison)
        <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--sand)">
          <p class="muted" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.06em;font-weight:800">Historique des frais de livraison</p>
          <ul class="cr-history reset">
            @foreach ($deliveryProposals as $proposal)
              <li>
                <span>
                  {{ $proposal->proposedBy->name }} — {{ number_format($proposal->amount, 0, ',', ' ') }} FCFA
                  <x-adm.pill :status="$proposal->status" />
                  @if ($proposal->note)
                    <br><span class="muted" style="font-size:.8rem">{{ $proposal->note }}</span>
                  @endif
                </span>
                <time>{{ $proposal->created_at->translatedFormat('d/m/Y H:i') }}</time>
              </li>
            @endforeach
          </ul>

          @if ($canAcceptDeliveryFee || $canProposeDeliveryFee)
            <div style="display:flex;gap:.6rem;flex-wrap:wrap;margin-top:1rem">
              @if ($canAcceptDeliveryFee)
                <button class="btn sm" wire:click="acceptDeliveryFee" data-confirm="Accepter ce montant de frais de livraison et confirmer la commande ?">Accepter</button>
              @endif
            </div>
            @if ($canProposeDeliveryFee)
              <form wire:submit="proposeDeliveryFee" style="margin-top:.8rem">
                <div class="field-row">
                  <div class="field">
                    <label>Contre-proposition (FCFA)</label>
                    <input type="number" min="0" wire:model="proposedAmount">
                    @error('proposedAmount') <span class="inline-err">{{ $message }}</span> @enderror
                  </div>
                </div>
                <div class="field">
                  <label>Note <span class="muted">(facultatif)</span></label>
                  <textarea wire:model="proposalNote" rows="2"></textarea>
                  @error('proposalNote') <span class="inline-err">{{ $message }}</span> @enderror
                </div>
                <button type="submit" class="btn sm ghost">Faire une contre-proposition</button>
              </form>
            @endif
          @else
            <p class="muted" style="font-size:.82rem;margin-top:.6rem">En attente de la réponse de l'autre partie…</p>
          @endif
        </div>
      @endif

      @if ($o->status === \App\Enums\CropOrderStatus::CommandeConfirmee)
        <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--sand)">
          <dl class="kv order-summary-kv">
            <dt>Frais de livraison convenus</dt>
            <dd>{{ number_format((float) $o->delivery_fee_agreed, 0, ',', ' ') }} FCFA</dd>
            <dt>Total</dt>
            <dd><b>{{ number_format((float) $o->total_amount, 0, ',', ' ') }} FCFA</b></dd>
          </dl>

          @if ($canDeclareBuyerSelfArranged || $canDeclareProducerSelfArranged || $canRequestDeliveryAssistance)
            <p class="muted" style="font-size:.78rem;margin-top:1rem">Comment la livraison sera-t-elle organisée ?</p>
            <div style="display:flex;gap:.6rem;flex-wrap:wrap;margin-top:.4rem">
              @if ($canDeclareBuyerSelfArranged)
                <button class="btn sm ghost" wire:click="declareSelfArrangedDelivery('acheteur')" data-confirm="Confirmez-vous que vous récupérez la marchandise vous-même (ou avec votre propre livreur), sans l'aide de l'administration ?">
                  Je récupère moi-même / j'ai mon livreur
                </button>
              @endif
              @if ($canDeclareProducerSelfArranged)
                <button class="btn sm ghost" wire:click="declareSelfArrangedDelivery('producteur')" data-confirm="Confirmez-vous que vous livrez vous-même (ou avec votre propre livreur), sans l'aide de l'administration ?">
                  Je livre moi-même / j'ai mon livreur
                </button>
              @endif
              @if ($canRequestDeliveryAssistance)
                <button class="btn sm" wire:click="requestDeliveryAssistance" data-confirm="Demander l'aide de l'administration pour organiser la livraison ?">
                  🚚 Aide livraison
                </button>
              @endif
            </div>
          @endif
        </div>
      @endif

      @if ($o->status === \App\Enums\CropOrderStatus::LivraisonAutoOrganisee)
        <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--sand)">
          <p style="margin:0;font-weight:700">
            Livraison auto-organisée —
            {{ $o->self_arranged_mode === 'acheteur' ? "l'acheteur récupère lui-même / a son propre livreur" : 'le producteur livre lui-même / a son propre livreur' }}.
          </p>
          <p class="muted" style="font-size:.84rem">Déclaré par {{ $o->selfArrangedBy?->name }} le {{ $o->self_arranged_at?->translatedFormat('d F Y à H\hi') }}.</p>
          <div style="display:flex;gap:.6rem;flex-wrap:wrap;margin-top:.8rem">
            @if ($canConfirmSelfArrangedDelivery)
              <button class="btn sm" wire:click="confirmSelfArrangedDelivery" data-confirm="Confirmez-vous avoir bien reçu la marchandise ?">J'ai reçu, confirmer</button>
            @endif
            @if ($canCancelSelfArrangedDelivery)
              <button type="button" class="btn sm ghost is-danger" wire:click="cancelSelfArrangedDelivery" data-confirm="Annuler la livraison auto-organisée et revenir aux 3 choix ?">Annuler</button>
            @endif
          </div>
        </div>
      @endif

      @if (in_array($o->status, [\App\Enums\CropOrderStatus::AideLivraison, \App\Enums\CropOrderStatus::LivraisonEnPreparation, \App\Enums\CropOrderStatus::LivraisonEnCours], true))
        <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--sand)">
          <p style="margin:0;font-weight:700">L'administration organise la livraison.</p>
          <p class="muted" style="font-size:.84rem">Statut actuel : {{ $o->status->label() }}</p>
        </div>
      @endif

      @if ($o->status === \App\Enums\CropOrderStatus::Livree)
        <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--sand)">
          <p style="margin:0;font-weight:700;color:var(--leaf)">Commande livrée.</p>
          <p class="muted" style="font-size:.84rem">
            {{ $o->self_arranged_mode ? 'Livraison auto-organisée.' : "Livraison organisée avec l'aide de l'administration." }}
          </p>
        </div>
      @endif
    </div>

  </div>

  @if ($activities->isNotEmpty())
    <div class="card pad-lg">
      <div class="card-head"><h3>Historique</h3></div>
      <ul class="cr-history reset">
        @foreach ($activities as $activity)
          @php $newStatus = $activity->properties['attributes']['status'] ?? null; @endphp
          <li>
            <span>
              @if ($activity->event === 'created')
                Commande créée
              @elseif ($newStatus)
                Statut : {{ \App\Enums\CropOrderStatus::from($newStatus)->label() }}
              @else
                Mise à jour
              @endif
            </span>
            <time>{{ $activity->created_at->translatedFormat('d/m/Y H:i') }}</time>
          </li>
        @endforeach
      </ul>
    </div>
  @endif

</div>

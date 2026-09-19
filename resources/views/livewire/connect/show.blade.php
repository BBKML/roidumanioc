@php
    $cr = $connectionRequest;
    $target = $cr->crop_offer_id ? $cr->cropOffer : $cr->buyerNeed;
    $targetLabel = $cr->crop_offer_id ? $cr->cropOffer->product_name : $cr->buyerNeed->product_wanted;
    $isTerminalNegative = in_array($cr->status, [\App\Enums\ConnectionRequestStatus::Refusee, \App\Enums\ConnectionRequestStatus::Annulee], true);

    // Résumé : une vraie proposition (dans le chat) prime, sinon les termes indiqués dès le
    // premier contact (`initial_proposal_terms` — sans ça, le producteur ouvrait cette page
    // et ne voyait NULLE PART ce que l'acheteur avait réellement demandé, seulement les
    // valeurs génériques de l'offre, avant même de cliquer « Accepter » — signalé en usage
    // réel), et seulement à défaut des deux la demande d'origine (offre/besoin). Mêmes
    // données que celles reprises dans la collaboration une fois confirmée, cf.
    // ConnectionRequest::createCollaborationAgreement().
    $proposalTerms = $cr->latestProposal()?->proposal_terms ?? $cr->initial_proposal_terms;
    $summaryUnit = \App\Enums\CropUnit::tryFrom($proposalTerms['unit'] ?? $target->unit->value);
@endphp
<div class="connect-shell">

  <div class="page-intro" style="margin-bottom:0">
    <div>
      <h2>{{ $targetLabel }}</h2>
      <p>
        Producteur : <b>{{ $cr->producerProfile->business_name }}</b>
        · Acheteur : <b>{{ $cr->buyerProfile->company_name ?: 'Acheteur' }}</b>
      </p>
    </div>
    <x-adm.pill :status="$cr->status" />
  </div>

  @if ($isViewerAdmin)
    <div class="card pad-lg" style="border:1px dashed var(--gold);display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap">
      <p style="margin:0;font-size:.84rem">
        <b>Vue administrateur — lecture seule.</b> Vous consultez cette demande en tant qu'administrateur, sans être ni le producteur ni l'acheteur concerné.
      </p>
      <a class="btn sm ghost" href="{{ route('admin.connection-requests') }}" wire:navigate>← Retour aux demandes</a>
    </div>
  @endif

  @if ($isTerminalNegative)
    <div class="card pad-lg" style="border-color:var(--danger)">
      <p style="margin:0;font-weight:700;color:var(--danger)">
        Cette demande est {{ $cr->status === \App\Enums\ConnectionRequestStatus::Annulee ? 'annulée' : 'refusée' }}.
      </p>
    </div>
  @endif

  @if ($collaboration)
    <div class="card pad-lg" style="border-color:var(--leaf)">
      <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap">
        <p style="margin:0;font-weight:700;color:var(--leaf)">Collaboration confirmée — suivez l'accord, le paiement et la livraison.</p>
        <a href="{{ route('learner.collaborations.show', $collaboration) }}" wire:navigate class="btn">Voir ma collaboration →</a>
      </div>
    </div>
  @endif

  {{-- Discussion en plein cadre (façon WhatsApp) + panneau latéral avec le suivi de la
       demande — le chat porte désormais aussi les propositions structurées, la section
       "Progression" ne garde que les actions qui ne concernent pas une proposition
       précise (accepter la demande initiale, passer en négociation, refuser, annuler). --}}
  <div class="connect-layout">
    <div class="card chat-panel">
      <livewire:connect.conversation :connection-request="$cr" :key="'conversation-'.$cr->id" />
    </div>

    <aside class="connect-side">
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
                <b>{{ $cr->status->label() }}</b>
                <span>{{ $cr->updated_at->translatedFormat('d F Y à H\hi') }}</span>
              </div>
            </div>
          @endif
        </div>

        @if ($canAccept || $canCancel || $canRefuse || $canConfirmCollaboration)
          <div style="display:flex;gap:.6rem;flex-wrap:wrap;margin-top:1.4rem;padding-top:1.2rem;border-top:1px solid var(--sand)">
            @if ($canAccept)
              <button class="btn sm" wire:click="accept">Accepter</button>
            @endif
            @if ($canConfirmCollaboration)
              <button class="btn sm" wire:click="confirmCollaboration">Confirmer la collaboration</button>
            @endif
            @if ($canRefuse)
              <button class="btn sm ghost is-danger" wire:click="refuse" data-confirm="Refuser cette demande ?">Refuser</button>
            @endif
            @if ($canCancel)
              <button class="btn sm ghost is-danger" wire:click="cancel" data-confirm="Annuler cette demande ?">Annuler ma demande</button>
            @endif
          </div>
        @endif
      </div>

      {{-- Le message initial n'est plus ré-affiché ici : il apparaît désormais comme la
           toute première bulle du fil de discussion ci-contre (cf. CreateConnectionRequest),
           l'afficher aussi ici serait redondant. --}}
      <x-adm.order-summary
        :product="$targetLabel"
        :quantity="$proposalTerms['quantity'] ?? $target->quantity"
        :unit="$summaryUnit?->label()"
        :price-total="$proposalTerms['price_total'] ?? null"
        :zone="$cr->crop_offer_id ? $cr->cropOffer->location : $cr->buyerNeed->location"
        :producer-name="$cr->producerProfile->business_name"
        :buyer-name="$cr->buyerProfile->company_name ?: 'Acheteur'"
      />

      <div class="card pad-lg">
        <div class="card-head"><h3>Historique</h3></div>
        @if ($activities->isEmpty())
          <p class="muted" style="font-size:.84rem">Aucun historique.</p>
        @else
          <ul class="cr-history reset">
            @foreach ($activities as $activity)
              @php $newStatus = $activity->properties['attributes']['status'] ?? null; @endphp
              <li>
                <span>
                  @if ($activity->event === 'created')
                    Demande créée
                  @elseif ($newStatus)
                    Statut : {{ \App\Enums\ConnectionRequestStatus::from($newStatus)->label() }}
                  @else
                    Mise à jour
                  @endif
                </span>
                <time>{{ $activity->created_at->translatedFormat('d/m/Y H:i') }}</time>
              </li>
            @endforeach
          </ul>
        @endif
      </div>
    </aside>
  </div>

</div>

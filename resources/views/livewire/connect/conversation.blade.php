@php
    $statusLabel = fn ($v) => \App\Enums\ConnectionRequestStatus::from($v)->label();
@endphp
<div wire:poll.5s class="chat-shell">

  <div class="chat-thread">
    {{-- Termes indiqués dès le premier contact (Connect\RequestOffer/RequestNeed) : tant
         qu'aucune vraie proposition n'existe encore dans le fil (avant acceptation), affiche
         un aperçu — sinon le fil paraît vide alors qu'une quantité/un prix a bien été
         indiqué (signalé en usage réel). Disparaît de lui-même dès l'acceptation, remplacé
         par la vraie carte de proposition (auto-créée, cf. Connect\Show::accept()). --}}
    @if ($connectionRequest->initial_proposal_terms && ! $latestProposalId)
      @php
        $ip = $connectionRequest->initial_proposal_terms;
        $ipUnit = \App\Enums\CropUnit::tryFrom($ip['unit'] ?? '')?->label();
      @endphp
      <div class="pending-proposal-note">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v4l3 3"/><circle cx="12" cy="12" r="9"/></svg>
        <span>
          <b>{{ $connectionRequest->requester->name }}</b> propose {{ number_format((float) $ip['quantity'], 2, ',', ' ') }} {{ $ipUnit }}
          @if (! empty($ip['price_total']))
            pour {{ number_format($ip['price_total'], 0, ',', ' ') }} FCFA
          @endif
          — transmis comme une vraie proposition dès l'acceptation de la demande.
        </span>
      </div>
    @endif

    @forelse ($messages as $chatMessage)
      @php
        $mine = $chatMessage->sender_id === auth()->id();
        $isLatestProposal = $chatMessage->isProposal() && $chatMessage->id === $latestProposalId;
      @endphp
      <div class="chat-row {{ $mine ? 'mine' : '' }}">
        @if ($chatMessage->isProposal())
          @php $terms = $chatMessage->proposal_terms ?? []; @endphp
          <div class="chat-bubble proposal-bubble {{ $mine ? 'mine' : '' }}">
            <div class="proposal-head">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 8l9-5 9 5v8l-9 5-9-5z"/><path d="M3 8l9 5 9-5"/></svg>
              Proposition
            </div>
            <p class="proposal-terms">
              {{ number_format((float) ($terms['quantity'] ?? 0), 2, ',', ' ') }} {{ \App\Enums\CropUnit::tryFrom($terms['unit'] ?? '')?->label() }}
              @if (! empty($terms['price_total']))
                <br>{{ number_format($terms['price_total'], 0, ',', ' ') }} FCFA
              @endif
            </p>
            @if ($chatMessage->displayBody($isModerating) !== '')
              <p class="proposal-note">{{ $chatMessage->displayBody($isModerating) }}</p>
            @endif
            @if ($isModerating && $chatMessage->contains_flagged_content)
              <span class="chat-flag">⚠ Coordonnée détectée ({{ implode(', ', array_keys($chatMessage->flagged_patterns ?? [])) }})</span>
            @endif
            <time>{{ $chatMessage->sender->name }} · {{ $chatMessage->created_at->translatedFormat('d/m H:i') }}</time>

            @if ($isLatestProposal)
              @if ($canConfirmCollaboration || $canRefuse)
                <div class="proposal-actions">
                  @if ($canConfirmCollaboration)
                    <button type="button" class="btn sm" wire:click="acceptProposal" data-confirm="Accepter cette proposition et confirmer la collaboration ?">Accepter</button>
                  @endif
                  @if ($canRefuse)
                    <button type="button" class="btn sm ghost is-danger" wire:click="refuseProposal" data-confirm="Refuser cette proposition ? La demande sera close.">Refuser</button>
                  @endif
                </div>
              @elseif ($requestStatus === 'proposition')
                <span class="proposal-status">En attente de réponse…</span>
              @else
                <span class="proposal-status">{{ $statusLabel($requestStatus) }}</span>
              @endif
            @endif
          </div>
        @else
          <div class="chat-bubble {{ $mine ? 'mine' : '' }}">
            <p>{{ $chatMessage->displayBody($isModerating) }}</p>
            <time>{{ $chatMessage->sender->name }} · {{ $chatMessage->created_at->translatedFormat('d/m H:i') }}</time>
            @if ($isModerating && $chatMessage->contains_flagged_content)
              <span class="chat-flag">⚠ Coordonnée détectée ({{ implode(', ', array_keys($chatMessage->flagged_patterns ?? [])) }})</span>
            @endif
          </div>
        @endif
      </div>
    @empty
      <p class="muted" style="font-size:.84rem">Aucun message pour l'instant — lancez la conversation.</p>
    @endforelse
  </div>

  @if ($canSend)
    <div class="chat-composer">
      @if ($showProposalForm)
        <div class="proposal-form">
          <div class="field-row cols-3">
            <div class="field">
              <label>Quantité</label>
              <input type="text" inputmode="decimal" wire:model="proposalQuantity" placeholder="Ex. 5">
              @error('proposalQuantity') <span class="inline-err">{{ $message }}</span> @enderror
            </div>
            <div class="field">
              <label>Unité</label>
              <select wire:model="proposalUnit">
                @foreach ($units as $u)
                  <option value="{{ $u->value }}">{{ $u->label() }}</option>
                @endforeach
              </select>
            </div>
            <div class="field">
              <label>Prix total (FCFA) <span class="muted">facultatif</span></label>
              <input type="number" min="1" wire:model="proposalPrice" placeholder="Ex. 500000">
              @error('proposalPrice') <span class="inline-err">{{ $message }}</span> @enderror
            </div>
          </div>
          <div class="field">
            <label>Note <span class="muted">facultatif</span></label>
            <textarea wire:model="proposalNote" rows="2" placeholder="Précisions sur votre proposition…"></textarea>
            @error('proposalNote') <span class="inline-err">{{ $message }}</span> @enderror
          </div>
          <div style="display:flex;gap:.6rem">
            <button type="button" class="btn sm" wire:click="sendProposal" wire:loading.attr="disabled" wire:target="sendProposal">Envoyer la proposition</button>
            <button type="button" class="btn sm ghost" wire:click="cancelProposal">Annuler</button>
          </div>
        </div>
      @else
        <form wire:submit="send" class="chat-composer-row">
          @if ($canPropose)
            <button type="button" class="btn sm ghost" wire:click="startProposal" title="Faire une proposition structurée">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
              Proposer
            </button>
          @endif
          <div class="field" style="flex:1;margin:0">
            <textarea wire:model="body" rows="1" placeholder="Écrire un message…" maxlength="2000"></textarea>
            @error('body') <span class="inline-err">{{ $message }}</span> @enderror
          </div>
          <button type="submit" class="btn" wire:loading.attr="disabled" wire:target="send">Envoyer</button>
        </form>
      @endif
    </div>
  @endif

</div>

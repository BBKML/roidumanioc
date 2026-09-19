@php $money = fn ($n) => number_format((int) $n, 0, ',', ' ').' FCFA'; @endphp
<div style="display:flex;flex-direction:column;gap:1.4rem">

  <div class="page-intro">
    <div>
      <h2>Paiements à vérifier</h2>
      <p>Un <b>contrôle automatique</b> compare le montant déclaré et la capture aux données attendues, et signale les réutilisations. Il aide à trier — <b>confirmez uniquement après avoir vu l'argent sur votre compte marchand</b>. Confirmer débloque automatiquement la formation ou la commande.</p>
    </div>
  </div>

  <div class="grid g-3" style="margin-bottom:.2rem">
    <x-adm.stat label="À vérifier" :value="$stats['pending']" :hint="$stats['pending'] ? $money($stats['pendingSum']).' en attente' : 'tout est à jour'" :tone="$stats['pending'] ? 'up' : 'flat'"><rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18"/></x-adm.stat>
    <x-adm.stat label="Confirmés" :value="$stats['confirmed']" :hint="$money($stats['confirmedSum'])" tone="up"><path d="M20 6 9 17l-5-5"/></x-adm.stat>
    <x-adm.stat label="Refusés" :value="$stats['refused']"><path d="M18 6 6 18M6 6l12 12"/></x-adm.stat>
  </div>

  <div class="tabs">
    <button class="{{ $filter === 'a_verifier' ? 'on' : '' }}" wire:click="setFilter('a_verifier')">À vérifier ({{ $stats['pending'] }})</button>
    <button class="{{ $filter === 'confirme' ? 'on' : '' }}" wire:click="setFilter('confirme')">Confirmés</button>
    <button class="{{ $filter === 'refuse' ? 'on' : '' }}" wire:click="setFilter('refuse')">Refusés</button>
    <button class="{{ $filter === 'tous' ? 'on' : '' }}" wire:click="setFilter('tous')">Tous</button>
  </div>
  <x-adm.loading-note target="setFilter" />

  <div>
    @forelse ($payments as $p)
      @php
        $c = $p->check_result ?? [];
        $riskCls = ['high' => 'bad', 'medium' => 'warn', 'low' => 'ok'][$p->risk()] ?? 'warn';
      @endphp
      <div class="pay-card" wire:key="pay-{{ $p->id }}">
        <div class="pay-card-head">
          <span class="avatar" style="width:32px;height:32px;font-size:.74rem">{{ $p->user?->initials() }}</span>
          <b>{{ $p->user?->name ?? '—' }}</b>
          <span class="ref">{{ $p->reference }}</span>
          <span class="verdict {{ $riskCls }}" style="margin:0 0 0 auto">Risque {{ ['high'=>'élevé','medium'=>'moyen','low'=>'faible'][$p->risk()] ?? '—' }}</span>
          <x-adm.pill :status="$p->status" />
        </div>

        <div class="pay-card-grid">
          <div><span class="k">Objet</span>{{ $p->label }}</div>
          <div><span class="k">Prix attendu</span><span class="nums">{{ $money($p->amount) }}</span></div>
          <div><span class="k">Moyen</span>{{ $p->method->label() }}</div>
          <div><span class="k">Reçu le</span>{{ $p->submitted_at?->format('d/m/Y H:i') }}</div>
        </div>

        <div class="pay-check">
          <div>
            @switch($c['amount'] ?? null)
              @case('ok')
                <span class="verdict ok">✔ Montant déclaré conforme au prix</span>
                @break
              @case('insufficient')
                <span class="verdict bad">⚠ Montant déclaré insuffisant : {{ $money(abs($c['gap'] ?? 0)) }} de moins</span>
                @break
              @case('excess')
                <span class="verdict warn">⚠ Montant déclaré supérieur : +{{ $money(abs($c['gap'] ?? 0)) }}</span>
                @break
              @default
                <span class="verdict warn">⚠ Montant à contrôler</span>
            @endswitch

            @foreach ($p->flags() as $flag)
              @continue($flag === 'amount_insufficient' || $flag === 'amount_excess')
              <div class="verdict {{ in_array($flag, ['duplicate_transaction','duplicate_proof']) ? 'bad' : 'warn' }}" style="margin-top:.4rem">⚠ {{ \App\Models\Payment::flagLabel($flag) }}</div>
            @endforeach

            <div class="row2">Montant déclaré par le client : <b>{{ $p->declared_amount !== null ? $money($p->declared_amount) : '—' }}</b></div>
            <div class="row2">N° de transaction : <b>{{ $p->transaction_id ?: 'non fourni' }}</b></div>
            <p class="hint">Le contrôle porte sur les infos saisies. Vérifiez toujours la réception réelle sur votre compte {{ $p->method->label() }}.</p>
          </div>
          <div class="pc-proof">
            @if ($p->proof_path)
              <a href="{{ route('payments.proof', $p) }}" target="_blank" rel="noopener">
                <img src="{{ route('payments.proof', $p) }}" alt="reçu {{ $p->reference }}">
              </a>
            @else
              <div class="no-proof">Capture non téléversée — voir WhatsApp</div>
            @endif
          </div>
        </div>

        @if ($p->status->value === 'a_verifier')
          <div x-data="{ seen: false }">
            <label class="switch-row" style="margin:.2rem 0 .7rem">
              <input type="checkbox" x-model="seen">
              J'ai vérifié la réception de <b>&nbsp;{{ $money($p->amount) }}&nbsp;</b> sur le compte {{ $p->method->label() }}
            </label>
            <div class="row-actions" style="justify-content:flex-start">
              <button class="btn sm" x-bind:disabled="!seen" wire:click="confirm({{ $p->id }})" data-confirm="Confirmer définitivement ce paiement ? L'accès sera débloqué.">
                <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M20 6 9 17l-5-5"/></svg>
                J'ai reçu l'argent — confirmer
              </button>
              <button class="btn sm danger" wire:click="startReject({{ $p->id }})">Refuser</button>
            </div>

            @if ($rejecting === $p->id)
              <div class="field" style="margin-top:.8rem;max-width:520px">
                <label>Motif du refus (communiqué au client)</label>
                <textarea wire:model="rejectReason" rows="2" placeholder="Ex : aucun versement retrouvé sur le compte Wave à cette date."></textarea>
                @error('rejectReason') <span class="inline-err">{{ $message }}</span> @enderror
                <div class="row-actions" style="justify-content:flex-start;margin-top:.5rem">
                  <button class="btn sm danger" wire:click="reject">Confirmer le refus</button>
                  <button class="btn sm ghost" wire:click="$set('rejecting', null)">Annuler</button>
                </div>
              </div>
            @endif
          </div>
        @else
          <p class="muted" style="font-size:.8rem;margin:.4rem 0 0">
            {{ $p->status->label() }} par {{ $p->confirmedBy?->name ?? 'l\'équipe' }} le {{ $p->confirmed_at?->format('d/m/Y H:i') }}
            @if ($p->rejection_reason) — « {{ $p->rejection_reason }} » @endif
          </p>
        @endif
      </div>
    @empty
      <div class="table-wrap"><div class="empty">
        <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
        <p>Aucun paiement dans cette vue.</p>
      </div></div>
    @endforelse
  </div>

  {{ $payments->links() }}

</div>

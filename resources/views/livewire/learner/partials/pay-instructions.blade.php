@php
    $money = fn ($n) => number_format((int) $n, 0, ',', ' ').' FCFA';
    $accounts = [
        'wave' => ['Wave', $settings->wave],
        'orange_money' => ['Orange Money', $settings->orange],
        'mtn_momo' => ['MTN MoMo', $settings->mtn],
        'moov_money' => ['Moov Money', $settings->moov],
        'virement' => [$settings->bank_name ?: 'Virement bancaire', $settings->rib],
        'carte' => ['Carte / International', $settings->intl_link],
    ];
    [$accountLabel, $accountValue] = $accounts[$method] ?? $accounts['wave'];
@endphp

<div class="pay-methods">
  @foreach ($methods as $m)
    <button type="button" class="pay-method {{ $method === $m->value ? 'on' : '' }}" wire:click="$set('method', '{{ $m->value }}')">{{ $m->label() }}</button>
  @endforeach
</div>

<div class="pay-panel">
  @if ($method === 'carte')
    <p style="font-size:.86rem;margin:0 0 .9rem">Paiement par <b>carte bancaire</b> (Visa / Mastercard) — pratique depuis l'étranger.</p>
    @if ($accountValue)
      <a class="btn gold" href="{{ $accountValue }}" target="_blank" rel="noopener">Payer {{ $money($amountExpected) }} par carte →</a>
    @endif
    <ol style="margin:.8rem 0 0;padding-left:1.2rem;line-height:1.75">
      <li>Réglez sur la page sécurisée.</li>
      <li>Notez bien votre nom dans le motif.</li>
      <li>Revenez ici et déclarez le paiement ci-dessous.</li>
    </ol>
  @else
    <div class="num">
      @if ($method === 'virement'){{ $accountLabel }} — @endif
      <span>{{ $accountValue ?: 'à configurer' }}</span>
    </div>
    <ol style="margin:.8rem 0 0;padding-left:1.2rem;line-height:1.75">
      <li>Envoyez <b>{{ $money($amountExpected) }}</b> {{ $method === 'virement' ? 'sur le compte' : 'au numéro' }} ci-dessus via <b>{{ $accountLabel }}</b>.</li>
      <li>Dans le motif du dépôt, mettez votre <b>nom complet</b>.</li>
      <li>Envoyez la capture du reçu sur WhatsApp puis déclarez le paiement ci-dessous.</li>
    </ol>
    @if ($whatsapp)
      <a class="btn wa" style="margin-top:1rem" href="https://wa.me/{{ $whatsapp }}?text={{ rawurlencode('Bonjour, paiement pour : '.$itemLabel) }}" target="_blank" rel="noopener">
        <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 5h16v11H9l-4 4V5z"/></svg>
        Envoyer la preuve sur WhatsApp
      </a>
    @endif
  @endif
</div>

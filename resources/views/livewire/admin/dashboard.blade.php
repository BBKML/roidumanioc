@php
    $money = fn ($n) => number_format((int) $n, 0, ',', ' ').' FCFA';
    $maxBar = max(1, $bars->max('value'));
@endphp
<div style="display:flex;flex-direction:column;gap:1.4rem" wire:poll.60s>

  <div class="page-intro">
    <div>
      <h2>Bonjour {{ \Illuminate\Support\Str::of(auth()->user()->name)->explode(' ')->first() }}</h2>
      <p>Voici l'activité du royaume aujourd'hui.</p>
    </div>
    <a class="btn" href="{{ route('admin.formations') }}" wire:navigate>
      <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
      Nouvelle formation
    </a>
  </div>

  <div class="grid g-4">
    <x-adm.stat label="Membres actifs" :value="$activeMembers" hint="comptes actifs"><circle cx="9" cy="8" r="3.2"/><path d="M3.5 20c.7-3.4 3-5 5.5-5s4.8 1.6 5.5 5"/></x-adm.stat>
    <x-adm.stat label="Formations publiées" :value="$publishedFormations" :hint="$totalFormations.' au total'"><path d="M4 4v16l7-2 7 2V4l-7 2-7-2z"/></x-adm.stat>
    <x-adm.stat label="Paiements à vérifier" :value="$toVerify->count()" :hint="$toVerify->count() ? $money($toVerify->sum('amount')).' en attente' : 'tout est à jour'" :tone="$toVerify->count() ? 'up' : 'flat'"><rect x="3" y="6" width="18" height="13" rx="2"/><path d="M3 10h18"/></x-adm.stat>
    <x-adm.stat label="Recettes encaissées" :value="$money($revenue)" :hint="$money($revenueOnline).' en ligne · '.$money($revenueDelivery).' à la livraison'" tone="up"><circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/><path d="M2 3h3l2.5 13h11L21 7H6"/></x-adm.stat>
  </div>

  @if ($ordersToPrepare || $ordersInDelivery)
    <div class="card" style="display:flex;gap:2rem;flex-wrap:wrap;align-items:center">
      <div><span class="k" style="font-size:.7rem;text-transform:uppercase;color:var(--ink-soft);font-weight:800">Commandes à préparer</span><br><b style="font-family:'Fraunces',serif;font-size:1.6rem">{{ $ordersToPrepare }}</b></div>
      <div><span class="k" style="font-size:.7rem;text-transform:uppercase;color:var(--ink-soft);font-weight:800">En livraison</span><br><b style="font-family:'Fraunces',serif;font-size:1.6rem">{{ $ordersInDelivery }}</b></div>
      <a class="btn ghost" href="{{ route('admin.orders') }}" wire:navigate style="margin-left:auto">Ouvrir les commandes →</a>
    </div>
  @endif

  <div class="grid g-2">
    <div class="card pad-lg">
      <div class="card-head"><h3>Inscriptions validées</h3><span class="muted">5 derniers mois</span></div>
      <div class="bars-wrap"><div class="bars">
        @foreach ($bars as $bar)
          <div class="b" style="height:{{ max(8, $bar['value'] / $maxBar * 100) }}%" title="{{ $bar['value'] }} inscription(s)"><span>{{ $bar['label'] }}</span></div>
        @endforeach
      </div></div>
    </div>
    <div class="card pad-lg">
      <div class="card-head"><h3>Raccourcis</h3></div>
      <ul class="activity reset">
        <li><span class="di"><svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg></span>
          <div><a href="{{ route('admin.content') }}" wire:navigate><b>Modifier le contenu du site</b></a><br><time>textes, titres et images de la vitrine</time></div></li>
        <li><span class="di"><svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4v16l7-2 7 2V4l-7 2-7-2z"/></svg></span>
          <div><a href="{{ route('admin.formations') }}" wire:navigate><b>Gérer les formations et leçons</b></a><br><time>{{ $totalFormations }} formation(s)</time></div></li>
        <li><span class="di"><svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v6M12 15v6M3 12h6M15 12h6"/></svg></span>
          <div><a href="{{ route('admin.marketplace') }}" wire:navigate><b>Modérer la marketplace</b></a><br><time>{{ $pendingListings }} annonce(s) en attente</time></div></li>
        <li><span class="di"><svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 5h16v11H9l-4 4V5z"/></svg></span>
          <div><a href="{{ route('admin.messages') }}" wire:navigate><b>Répondre aux messages</b></a><br><time>{{ $newMessages }} message(s) à traiter</time></div></li>
      </ul>
    </div>
  </div>

  <div>
    <div class="card-head"><h3>Paiements à vérifier</h3><a class="link-btn" href="{{ route('admin.payments') }}" wire:navigate>Tout voir →</a></div>
    @forelse ($toVerify->take(4) as $p)
      <div class="pay-card">
        <div class="pay-card-head">
          <span class="avatar" style="width:32px;height:32px;font-size:.74rem">{{ $p->user?->initials() }}</span>
          <b>{{ $p->user?->name ?? '—' }}</b>
          <span class="ref">{{ $p->reference }}</span>
          <x-adm.pill :status="$p->status" />
        </div>
        <div class="pay-card-grid">
          <div><span class="k">Objet</span>{{ $p->label }}</div>
          <div><span class="k">Prix attendu</span><span class="nums">{{ $money($p->amount) }}</span></div>
          <div><span class="k">Moyen</span>{{ $p->method->label() }}</div>
          <div><span class="k">Reçu le</span>{{ $p->submitted_at?->format('d/m/Y') }}</div>
        </div>
        @php $c = $p->check_result ?? []; @endphp
        <div>
          @if (($c['proof'] ?? null) === 'missing')
            <span class="verdict warn">⚠ Aucune capture — à demander sur WhatsApp</span>
          @elseif (($c['amount'] ?? null) === 'ok')
            <span class="verdict ok">✔ Montant déclaré conforme au prix</span>
          @elseif (($c['amount'] ?? null) === 'insufficient')
            <span class="verdict bad">⚠ Montant insuffisant : {{ $money(abs($c['gap'] ?? 0)) }}</span>
          @elseif (($c['amount'] ?? null) === 'excess')
            <span class="verdict warn">⚠ Montant supérieur : +{{ $money(abs($c['gap'] ?? 0)) }}</span>
          @else
            <span class="verdict warn">⚠ À contrôler</span>
          @endif
        </div>
      </div>
    @empty
      <div class="table-wrap"><div class="empty">
        <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5"/></svg>
        <p>Aucun paiement en attente. Tout est à jour&nbsp;!</p>
      </div></div>
    @endforelse
  </div>

  {{-- Mise en relation (ex-écran séparé /admin/mise-en-relation, fusionné ici — Phase 16) --}}
  <div>
    <div class="card-head"><h3>Mise en relation — producteurs &amp; acheteurs</h3></div>
    <div class="grid g-4">
      <x-adm.stat label="Producteurs" :value="$producersTotal" :hint="$producersVerified.' vérifié(s)'">
        <circle cx="9" cy="8" r="3.2"/><path d="M3.5 20c.7-3.4 3-5 5.5-5s4.8 1.6 5.5 5"/>
      </x-adm.stat>
      <x-adm.stat label="Acheteurs" :value="$buyersTotal" hint="profils créés">
        <path d="M4 9h16l-1.5 10a2 2 0 0 1-2 1.8H7.5a2 2 0 0 1-2-1.8L4 9z"/><path d="M8 9V7a4 4 0 0 1 8 0v2"/>
      </x-adm.stat>
      <x-adm.stat label="Offres" :value="$offersTotal" :hint="$offersPublished.' publiée(s)'">
        <path d="M3 8l9-5 9 5v8l-9 5-9-5z"/><path d="M3 8l9 5 9-5"/>
      </x-adm.stat>
      <x-adm.stat label="Besoins" :value="$needsTotal" :hint="$needsOpen.' ouvert(s)'">
        <circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="4"/>
      </x-adm.stat>
      <x-adm.stat label="Demandes de mise en relation" :value="$requestsTotal" :hint="$requestsActive.' en cours'">
        <path d="M9 15 15 9"/><path d="M11 6l1-1a4 4 0 0 1 6 6l-1 1"/><path d="M13 18l-1 1a4 4 0 0 1-6-6l1-1"/>
      </x-adm.stat>
      <x-adm.stat label="Collaborations" :value="$collaborationsTotal" :hint="$collaborationsOngoing.' en cours · '.$collaborationsDone.' terminée(s)'">
        <path d="M4 20c8 0 16-8 16-16-8 0-16 8-16 16z"/><path d="M4 20c2-6 6-10 12-12"/>
      </x-adm.stat>
      <x-adm.stat label="Litiges signalés" :value="$collaborationsDisputed" :hint="$collaborationsDisputed ? 'à traiter' : 'aucun'" :tone="$collaborationsDisputed ? 'up' : 'flat'">
        <path d="M4 22V3"/><path d="M4 4h13l-2.5 4L17 12H4"/>
      </x-adm.stat>
      <x-adm.stat label="Volume estimé des échanges" :value="$money($estimatedVolume)" hint="collaborations non annulées" tone="up">
        <circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/><path d="M2 3h3l2.5 13h11L21 7H6"/>
      </x-adm.stat>
    </div>
    <p class="muted" style="font-size:.8rem;margin:.8rem 0 0">
      Le montant final négocié entre les parties reste dans la messagerie et n'est saisi sur
      aucun écran de cette V1 — le volume ci-dessus retombe donc sur le prix/budget indicatif
      de l'offre ou du besoin d'origine, toujours présenté comme un <b>estimé</b>.
    </p>
  </div>

  {{-- Parcours de commande structuré (§1-§10) — système parallèle à la mise en relation
       ci-dessus, distinct du chat (voir CLAUDE.md). --}}
  <div>
    <div class="card-head"><h3>Commandes producteur</h3></div>
    <div class="grid g-4">
      <x-adm.stat label="Commandes" :value="$cropOrdersTotal" :hint="$cropOrdersPending.' en attente du producteur'">
        <circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/><path d="M2 3h3l2.5 13h11L21 7H6"/>
      </x-adm.stat>
      <x-adm.stat label="En négociation" :value="$cropOrdersInNegotiation" hint="frais de livraison">
        <path d="M9 15 15 9"/><path d="M11 6l1-1a4 4 0 0 1 6 6l-1 1"/><path d="M13 18l-1 1a4 4 0 0 1-6-6l1-1"/>
      </x-adm.stat>
      <x-adm.stat label="Confirmées" :value="$cropOrdersConfirmed" hint="en attente d'aide livraison">
        <path d="M20 6 9 17l-5-5"/>
      </x-adm.stat>
      <x-adm.stat label="En livraison" :value="$cropOrdersInDelivery" :hint="$deliveryAssistsPending.' aide(s) à traiter'" :tone="$deliveryAssistsPending ? 'up' : 'flat'">
        <path d="M1 3h13v13H1z"/><path d="M14 8h4l3 3v5h-7V8z"/><circle cx="6.5" cy="18.5" r="1.8"/><circle cx="17.5" cy="18.5" r="1.8"/>
      </x-adm.stat>
      <x-adm.stat label="Terminées" :value="$cropOrdersDone" :hint="$money($cropOrdersVolume).' livrés'" tone="up">
        <path d="M3 8l9-5 9 5v8l-9 5-9-5z"/><path d="M3 8l9 5 9-5"/>
      </x-adm.stat>
    </div>
  </div>

</div>

@php $user = auth()->user(); @endphp
<div style="display:flex;flex-direction:column;gap:1.4rem">

  <div class="page-intro">
    <div>
      <h2>Bonjour {{ \Illuminate\Support\Str::of($user->name)->explode(' ')->first() }} 🌱</h2>
      <p>Reprenez votre apprentissage là où vous vous êtes arrêté.</p>
    </div>
    <a class="btn ghost" href="{{ route('learner.catalog') }}" wire:navigate>
      <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4v16l7-2 7 2V4l-7 2-7-2z"/></svg>
      Parcourir le catalogue
    </a>
  </div>

  @if (($isProducer || $isBuyer) && $unreadMessages->isNotEmpty())
    {{-- Doit se remarquer dès la connexion, pas seulement dans la petite cloche du
         topbar (demande explicite) — placée en tout premier, avant même les formations. --}}
    <div class="card pad-lg" style="border:2px solid var(--gold);background:var(--gold-soft)">
      <div class="card-head">
        <h3 style="display:flex;align-items:center;gap:.5rem;margin:0">
          <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5h16v11H9l-4 4V5z"/></svg>
          {{ $unreadMessages->count() }} nouveau{{ $unreadMessages->count() > 1 ? 'x' : '' }} message{{ $unreadMessages->count() > 1 ? 's' : '' }}
        </h3>
        <button type="button" wire:click="markAllMessagesRead" class="link-btn">Tout marquer comme lu</button>
      </div>
      <ul class="reset" style="display:flex;flex-direction:column;gap:.6rem;margin:0">
        @foreach ($unreadMessages as $n)
          <li>
            <a href="{{ $n->data['url'] ?? '#' }}" wire:navigate wire:click="markMessageRead('{{ $n->id }}')"
               style="display:flex;justify-content:space-between;align-items:center;gap:1rem;padding:.7rem .9rem;background:var(--paper);border-radius:10px;border:1px solid var(--sand)">
              <span style="min-width:0">
                <b style="display:block;font-size:.86rem">{{ $n->data['message'] ?? $n->data['title'] ?? 'Nouveau message' }}</b>
                <span class="muted" style="font-size:.74rem">{{ $n->created_at->diffForHumans() }}</span>
              </span>
              <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex:none"><path d="M9 6l6 6-6 6"/></svg>
            </a>
          </li>
        @endforeach
      </ul>
    </div>
  @endif

  @if ($enrolled->isNotEmpty())
    <div class="grid g-2">
      @foreach ($enrolled as $enrollment)
        @php
          $f = $enrollment->formation;
          $total = $f->lessons->count();
          $done = $f->lessons->filter(fn ($l) => $completedLessonIds->has($l->id))->count();
          $pr = ['done' => $done, 'total' => $total, 'pct' => $total ? (int) round($done / $total * 100) : 0];
          $next = $f->lessons->first(fn ($l) => ! $completedLessonIds->has($l->id));
        @endphp
        <div class="card pad-lg" wire:key="enr-{{ $enrollment->id }}">
          <div style="display:flex;gap:1rem">
            @if ($f->image_path)
              <img src="{{ asset($f->image_path) }}" alt="" style="width:96px;height:72px;border-radius:10px;object-fit:cover;object-position:center 20%;flex:none">
            @endif
            <div style="min-width:0">
              <h3 style="font-size:1.05rem">{{ $f->title }}</h3>
              <p class="muted" style="margin:.2rem 0 0;font-size:.8rem">{{ $pr['done'] }}/{{ $pr['total'] }} leçons · {{ $pr['pct'] }}%</p>
            </div>
          </div>
          <div class="progress" style="margin:1rem 0 .8rem"><i style="width:{{ $pr['pct'] }}%"></i></div>
          <div style="display:flex;justify-content:space-between;align-items:center;gap:.6rem;flex-wrap:wrap">
            <span class="muted" style="font-size:.8rem">{{ $next ? 'Suivant : '.$next->title : 'Formation terminée 🎉' }}</span>
            <a class="btn sm" href="{{ route('learner.course', $f) }}" wire:navigate>{{ $pr['pct'] ? 'Continuer' : 'Commencer' }}</a>
          </div>
        </div>
      @endforeach
    </div>
  @else
    <div class="card"><div class="empty">
      <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4v16l7-2 7 2V4l-7 2-7-2z"/></svg>
      <p>Vous ne suivez encore aucune formation.</p>
      <a class="btn" href="{{ route('learner.catalog') }}" wire:navigate style="margin-top:.6rem">Voir le catalogue</a>
    </div></div>
  @endif

  @if ($pending->isNotEmpty())
    <div class="card">
      <div class="card-head"><h3>Paiement en cours de vérification</h3></div>
      @foreach ($pending as $enrollment)
        <div style="display:flex;justify-content:space-between;align-items:center;padding:.5rem 0">
          <span>{{ $enrollment->formation->title }}</span>
          <x-adm.pill :status="$enrollment->status" />
        </div>
      @endforeach
      <p class="muted" style="font-size:.78rem;margin:.6rem 0 0">Votre accès s'active dès que notre équipe a vérifié le paiement (généralement sous quelques heures).</p>
    </div>
  @endif

  @if ($isProducer)
    @php $stars = $producerAverageRating ? number_format((float) $producerAverageRating, 1, ',', ' ').'/5' : '—'; @endphp
    <div class="card pad-lg">
      <div class="card-head">
        <h3>Espace producteur — {{ $producerProfile->business_name }}</h3>
        <a class="link-btn" href="{{ route('learner.producer.offers') }}" wire:navigate>Mes offres →</a>
      </div>
      <div class="grid g-3">
        <x-adm.stat label="Produits publiés" :value="$producerOffersPublishedCount" :hint="$producerOffersTotalCount.' au total'">
          <path d="M3 8l9-5 9 5v8l-9 5-9-5z"/><path d="M3 8l9 5 9-5"/>
        </x-adm.stat>
        <x-adm.stat label="Demandes reçues" :value="$producerRequestsReceivedCount" :hint="$producerRequestsPendingCount.' en attente'" :tone="$producerRequestsPendingCount ? 'up' : 'flat'">
          <path d="M9 15 15 9"/><path d="M11 6l1-1a4 4 0 0 1 6 6l-1 1"/><path d="M13 18l-1 1a4 4 0 0 1-6-6l1-1"/>
        </x-adm.stat>
        <x-adm.stat label="Collaborations" :value="$producerCollaborationsOngoing" :hint="$producerCollaborationsDone.' terminée(s)'">
          <path d="M4 20c8 0 16-8 16-16-8 0-16 8-16 16z"/><path d="M4 20c2-6 6-10 12-12"/>
        </x-adm.stat>
        <x-adm.stat label="Note moyenne" :value="$stars" :hint="$producerReviewsCount.' avis'">
          <path d="M12 3l2.6 5.6 6.1.7-4.5 4.2 1.2 6-5.4-3-5.4 3 1.2-6-4.5-4.2 6.1-.7z"/>
        </x-adm.stat>
        <x-adm.stat label="Commandes" :value="$producerCropOrdersPendingCount" hint="en attente de réponse" :tone="$producerCropOrdersPendingCount ? 'up' : 'flat'">
          <circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/><path d="M2 3h3l2.5 13h11L21 7H6"/>
        </x-adm.stat>
      </div>

      @if ($producerCropOrdersActive->isNotEmpty())
        <p class="muted" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.06em;font-weight:800;margin:1.2rem 0 0">Commandes en cours</p>
        <div class="active-items">
          @foreach ($producerCropOrdersActive as $order)
            <a href="{{ route('learner.crop-orders.show', $order) }}" wire:navigate class="active-item" wire:key="dash-crop-order-p-{{ $order->id }}">
              <x-adm.pill :status="$order->status" />
              <span>{{ $order->product_name }} <span class="muted">· {{ $order->buyerProfile->company_name ?: 'Acheteur' }}</span></span>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
            </a>
          @endforeach
        </div>
        <a class="link-btn" style="display:inline-block;margin-top:.8rem" href="{{ route('learner.orders') }}" wire:navigate>Voir toutes les commandes reçues →</a>
      @endif

      @if ($producerActiveItems->isNotEmpty())
        <p class="muted" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.06em;font-weight:800;margin:1.2rem 0 0">En cours</p>
        <div class="active-items">
          @foreach ($producerActiveItems as $item)
            <a href="{{ $item['url'] }}" wire:navigate class="active-item" wire:key="dash-producer-{{ $item['url'] }}">
              <x-adm.pill :status="$item['status']" />
              <span>{{ $item['label'] }} <span class="muted">· {{ $item['otherParty'] }}</span></span>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
            </a>
          @endforeach
        </div>
      @endif
    </div>
  @endif

  @if ($isBuyer)
    <div class="card pad-lg">
      <div class="card-head">
        <h3>Espace acheteur</h3>
        <span>
          <a class="link-btn" href="{{ route('learner.buyer.needs') }}" wire:navigate>Mes besoins →</a>
          <a class="link-btn" href="{{ route('learner.buyer.favorites') }}" wire:navigate style="margin-left:.8rem">Mes favoris →</a>
        </span>
      </div>
      <div class="grid g-3">
        <x-adm.stat label="Besoins publiés" :value="$buyerNeedsOpenCount" :hint="$buyerNeedsTotalCount.' au total'">
          <circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="4"/>
        </x-adm.stat>
        <x-adm.stat label="Demandes reçues" :value="$buyerRequestsReceivedCount">
          <path d="M9 15 15 9"/><path d="M11 6l1-1a4 4 0 0 1 6 6l-1 1"/><path d="M13 18l-1 1a4 4 0 0 1-6-6l1-1"/>
        </x-adm.stat>
        <x-adm.stat label="Collaborations" :value="$buyerCollaborationsOngoing" :hint="$buyerCollaborationsDone.' terminée(s)'">
          <path d="M4 20c8 0 16-8 16-16-8 0-16 8-16 16z"/><path d="M4 20c2-6 6-10 12-12"/>
        </x-adm.stat>
        <x-adm.stat label="Producteurs favoris" :value="$buyerFavoritesCount">
          <path d="M12 21s-7-4.35-9.5-8.5C1 9 2.5 5.5 6 5c2-.3 3.7.8 6 3 2.3-2.2 4-3.3 6-3 3.5.5 5 4 3.5 7.5C19 16.65 12 21 12 21z"/>
        </x-adm.stat>
      </div>

      @if ($buyerCropOrdersActive->isNotEmpty())
        <p class="muted" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.06em;font-weight:800;margin:1.2rem 0 0">Mes commandes en cours</p>
        <div class="active-items">
          @foreach ($buyerCropOrdersActive as $order)
            <a href="{{ route('learner.crop-orders.show', $order) }}" wire:navigate class="active-item" wire:key="dash-crop-order-b-{{ $order->id }}">
              <x-adm.pill :status="$order->status" />
              <span>{{ $order->product_name }} <span class="muted">· {{ $order->producerProfile->business_name }}</span></span>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
            </a>
          @endforeach
        </div>
        <a class="link-btn" style="display:inline-block;margin-top:.8rem" href="{{ route('learner.orders') }}" wire:navigate>Voir toutes mes commandes produits →</a>
      @endif

      @if ($buyerActiveItems->isNotEmpty())
        <p class="muted" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.06em;font-weight:800;margin:1.2rem 0 0">En cours</p>
        <div class="active-items">
          @foreach ($buyerActiveItems as $item)
            <a href="{{ $item['url'] }}" wire:navigate class="active-item" wire:key="dash-buyer-{{ $item['url'] }}">
              <x-adm.pill :status="$item['status']" />
              <span>{{ $item['label'] }} <span class="muted">· {{ $item['otherParty'] }}</span></span>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
            </a>
          @endforeach
        </div>
      @endif

      @if ($buyerRecentOffers->isNotEmpty())
        <p class="muted" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.06em;font-weight:800;margin:1.2rem 0 .6rem">Offres récentes des producteurs</p>
        <div class="grid g-3">
          @foreach ($buyerRecentOffers as $offer)
            <div class="card pad-lg" style="display:flex;flex-direction:column" wire:key="dash-offer-{{ $offer->id }}">
              <div style="flex:1">
                <h3 style="font-size:.92rem;margin:0 0 .2rem">
                  {{ $offer->product_name }}
                  @if ($offer->variety)<span class="muted" style="font-weight:400"> · {{ $offer->variety }}</span>@endif
                </h3>
                <p class="muted" style="font-size:.8rem;margin:0 0 .5rem">
                  {{ $offer->producerProfile->business_name }}
                  @if ($offer->producerProfile->isVerified())
                    <span class="muted" title="Producteur vérifié">✅</span>
                  @endif
                  · {{ $offer->location }}
                </p>
                <dl class="kv order-summary-kv" style="margin-bottom:.5rem">
                  <dt>Quantité disponible</dt>
                  <dd>{{ number_format((float) $offer->quantity, 2, ',', ' ') }} {{ $offer->unit->label() }}</dd>
                  <dt>Prix</dt>
                  <dd>{{ $offer->price_indicative ? number_format($offer->price_indicative, 0, ',', ' ').' FCFA (indicatif)' : 'À négocier' }}</dd>
                  @if ($offer->available_from)
                    <dt>Disponible à partir du</dt>
                    <dd>{{ $offer->available_from->translatedFormat('d F Y') }}</dd>
                  @endif
                </dl>
                @if ($offer->description)
                  <p style="font-size:.82rem;margin:0 0 .8rem;white-space:pre-line">{{ \Illuminate\Support\Str::limit($offer->description, 140) }}</p>
                @endif
              </div>
              <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-top:1rem;padding-top:1rem;border-top:1px solid var(--sand)">
                <a class="btn sm ghost" href="{{ route('learner.buyer.offers.contact', $offer) }}" wire:navigate>Négocier</a>
                <a class="btn sm" href="{{ route('learner.buyer.crop-orders.create', $offer) }}" wire:navigate>Passer une commande</a>
              </div>
            </div>
          @endforeach
        </div>
        <a class="link-btn" style="display:inline-block;margin-top:.8rem" href="{{ route('producers.index') }}" wire:navigate>Voir tout le catalogue →</a>
      @endif
    </div>
  @endif

  @if (($isProducer || $isBuyer) && $connectUnreadNotificationsCount > $unreadMessages->count())
    {{-- Notifications de mise en relation autres que des messages (demande acceptée,
         paiement déclaré…) — pas de bannière dédiée pour celles-ci (déjà couvertes par
         la cloche du topbar), juste un rappel discret qu'il y en a. --}}
    <div class="card" style="display:flex;align-items:center;gap:.6rem">
      <svg class="ic muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 17h14l-1.4-2.1A6 6 0 0 1 16.5 11V9a4.5 4.5 0 0 0-9 0v2a6 6 0 0 1-1.1 3.9L5 17z"/></svg>
      <span class="muted" style="font-size:.84rem">
        {{ $connectUnreadNotificationsCount - $unreadMessages->count() }} autre(s) notification(s) de mise en relation non lue(s) — voir la cloche en haut de page.
      </span>
    </div>
  @endif

  <div class="card">
    <div class="card-head"><h3>Prochains événements</h3><a class="link-btn" href="{{ route('learner.community') }}" wire:navigate>Communauté →</a></div>
    @if ($events->isNotEmpty())
      <ul class="activity reset">
        @foreach ($events as $event)
          <li>
            <span class="di"><svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/></svg></span>
            <div><b>{{ $event->title }}</b><br><time>{{ $event->date_label ?: $event->starts_at?->format('d/m/Y') }} · {{ $event->type }}</time></div>
          </li>
        @endforeach
      </ul>
    @else
      <p class="muted" style="font-size:.82rem">Aucun événement programmé pour l'instant.</p>
    @endif
  </div>

  @php
    // Discret, en bas de page (ne doit pas concurrencer les formations) : s'adapte à ce
    // qui est déjà activé, et disparaît complètement une fois les deux profils créés.
    $connectHeadline = match (true) {
        $isProducer && $isBuyer => null,
        $isProducer => 'Vous achetez aussi du manioc ?',
        $isBuyer => 'Vous produisez aussi du manioc ?',
        default => 'Vous produisez ou recherchez du manioc ?',
    };
  @endphp
  @if ($connectHeadline)
    <div class="card" style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap">
      <div>
        <p style="margin:0;font-weight:600">{{ $connectHeadline }}</p>
        <p class="muted" style="margin:.2rem 0 0;font-size:.82rem">Activez votre profil pour publier vos offres ou vos besoins sur la marketplace.</p>
      </div>
      <div style="display:flex;gap:.5rem;flex-wrap:wrap">
        @unless ($isProducer)
          <a class="btn sm ghost" href="{{ route('learner.producer') }}" wire:navigate>Devenir producteur</a>
        @endunless
        @unless ($isBuyer)
          <a class="btn sm ghost" href="{{ route('learner.buyer') }}" wire:navigate>Devenir acheteur</a>
        @endunless
      </div>
    </div>
  @endif

</div>

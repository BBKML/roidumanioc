<main id="main">
<span id="top"></span>

{{-- Bandeau de recherche façon page de recherche marketplace : grande barre pilule +
     onglets de catégorie, au-dessus des filtres fins de la barre latérale. --}}
<section class="search-hero is-first">
  <div class="wrap">
    <x-catalog-mode-tabs active="negotiate" />
    <form class="search-box" wire:submit.prevent>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
      <input type="search" wire:model.live.debounce.300ms="search" placeholder="Rechercher un producteur, un produit…" aria-label="Rechercher">
      <button type="submit">Rechercher</button>
    </form>
    <div class="search-tabs" role="tablist" aria-label="Type de produit">
      <button type="button" wire:click="$set('activityType', '')" class="{{ $activityType === '' ? 'active' : '' }}" role="tab" aria-selected="{{ $activityType === '' ? 'true' : 'false' }}">Tous</button>
      @foreach ($activityTypes as $type)
        <button type="button" wire:click="$set('activityType', '{{ $type->value }}')" class="{{ $activityType === $type->value ? 'active' : '' }}" role="tab" aria-selected="{{ $activityType === $type->value ? 'true' : 'false' }}">{{ $type->label() }}</button>
      @endforeach
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap">
    {{-- Filtres en barre latérale sticky + résultats en grille, façon page de recherche
         marketplace : chaque filtre reste visible en permanence pendant le défilement des
         résultats, plutôt qu'une rangée de champs qu'il faut remonter voir. --}}
    <div class="catalog-layout">
      <aside class="catalog-filters">
        <div class="catalog-filters-head">
          <h2>Affiner</h2>
          @if ($search !== '' || $zone !== '' || $product !== '' || $activityType !== '' || $verifiedOnly || $availableNow || $minRating !== '')
            <button type="button" wire:click="resetFilters">Réinitialiser</button>
          @endif
        </div>
        <form class="filters" wire:submit.prevent>
          <div class="filter-group">
            <label for="f-product">Produit disponible</label>
            <select id="f-product" wire:model.live="product">
              <option value="">Tous les produits</option>
              @foreach ($products as $productName)
                <option value="{{ $productName }}">{{ $productName }}</option>
              @endforeach
            </select>
          </div>
          <div class="filter-group">
            <label for="f-zone">Zone</label>
            <select id="f-zone" wire:model.live="zone">
              <option value="">Toutes les zones</option>
              @foreach ($zones as $zoneName)
                <option value="{{ $zoneName }}">{{ $zoneName }}</option>
              @endforeach
            </select>
          </div>
          <div class="filter-group">
            <label for="f-rating">Note minimale</label>
            <select id="f-rating" wire:model.live="minRating">
              <option value="">Toutes les notes</option>
              <option value="4.5">4,5★ et plus</option>
              <option value="4">4★ et plus</option>
              <option value="3">3★ et plus</option>
            </select>
          </div>
          <label class="chk"><input type="checkbox" wire:model.live="availableNow"> Disponible maintenant</label>
          <label class="chk"><input type="checkbox" wire:model.live="verifiedOnly"> Producteur vérifié uniquement</label>
        </form>
      </aside>

      <div class="catalog-results">
        <div class="catalog-results-head">
          <p class="catalog-count"><b>{{ $producers->total() }}</b> producteur{{ $producers->total() > 1 ? 's' : '' }} trouvé{{ $producers->total() > 1 ? 's' : '' }}</p>
        </div>

        @if ($search !== '' || $zone !== '' || $product !== '' || $activityType !== '' || $verifiedOnly || $availableNow || $minRating !== '')
          <div class="filter-chips">
            @if ($search !== '')
              <span class="chip">« {{ $search }} »<button type="button" wire:click="$set('search', '')" aria-label="Retirer ce filtre">✕</button></span>
            @endif
            @if ($product !== '')
              <span class="chip">{{ $product }}<button type="button" wire:click="$set('product', '')" aria-label="Retirer ce filtre">✕</button></span>
            @endif
            @if ($zone !== '')
              <span class="chip">{{ $zone }}<button type="button" wire:click="$set('zone', '')" aria-label="Retirer ce filtre">✕</button></span>
            @endif
            @if ($activityType !== '')
              <span class="chip">{{ \App\Enums\ActivityType::from($activityType)->label() }}<button type="button" wire:click="$set('activityType', '')" aria-label="Retirer ce filtre">✕</button></span>
            @endif
            @if ($minRating !== '')
              <span class="chip">{{ str_replace('.', ',', $minRating) }}★ et plus<button type="button" wire:click="$set('minRating', '')" aria-label="Retirer ce filtre">✕</button></span>
            @endif
            @if ($availableNow)
              <span class="chip">Disponible maintenant<button type="button" wire:click="$set('availableNow', false)" aria-label="Retirer ce filtre">✕</button></span>
            @endif
            @if ($verifiedOnly)
              <span class="chip">Vérifié uniquement<button type="button" wire:click="$set('verifiedOnly', false)" aria-label="Retirer ce filtre">✕</button></span>
            @endif
          </div>
        @endif

        @if ($producers->isEmpty())
          <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
            <p>Aucun producteur ne correspond à ces critères.</p>
            <span>Essayez d'élargir votre recherche ou de retirer un filtre.</span>
          </div>
        @else
          <div class="offers reveal" wire:ignore.self>
            @foreach ($producers as $producer)
              @php $isFavorite = in_array($producer->id, $favoriteIds, true); @endphp
              <article class="offer" wire:key="producer-{{ $producer->id }}">
                <div class="ph">
                  @if ($producer->isVerified())
                    <span class="badge-verified">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                      Vérifié
                    </span>
                  @endif
                  <a href="{{ route('producers.show', $producer) }}">
                    @if ($producer->logo_path)
                      <img loading="lazy" decoding="async" src="{{ \Illuminate\Support\Facades\Storage::url($producer->logo_path) }}" alt="{{ $producer->business_name }}">
                    @else
                      <img loading="lazy" decoding="async" src="{{ asset('img/champ-manioc.jpg') }}" alt="{{ $producer->business_name }}">
                    @endif
                  </a>
                  @auth
                    <form method="POST" action="{{ route('producers.favorite.toggle', $producer) }}" class="fav-form">
                      @csrf
                      <button type="submit" class="fav-btn {{ $isFavorite ? 'is-fav' : '' }}" aria-pressed="{{ $isFavorite ? 'true' : 'false' }}" title="{{ $isFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris' }}">
                        <svg viewBox="0 0 24 24" fill="{{ $isFavorite ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-7-4.35-9.5-8.5C1 9 2.5 5.5 6 5c2-.3 3.7.8 6 3 2.3-2.2 4-3.3 6-3 3.5.5 5 4 3.5 7.5C19 16.65 12 21 12 21z"/></svg>
                      </button>
                    </form>
                  @else
                    <a href="{{ route('login') }}" class="fav-btn" title="Connectez-vous pour ajouter aux favoris">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s-7-4.35-9.5-8.5C1 9 2.5 5.5 6 5c2-.3 3.7.8 6 3 2.3-2.2 4-3.3 6-3 3.5.5 5 4 3.5 7.5C19 16.65 12 21 12 21z"/></svg>
                    </a>
                  @endauth
                </div>
                <div class="ob">
                  <span class="t">{{ $producer->activity_type->label() }}</span>
                  <h4><a href="{{ route('producers.show', $producer) }}">{{ $producer->business_name }}</a></h4>
                  <p class="loc">{{ $producer->zone }}</p>
                  <div class="rating-row">
                    <span class="rating-stars" aria-hidden="true">
                      @for ($i = 1; $i <= 5; $i++)
                        <svg class="{{ $i <= round((float) $producer->avg_rating) ? 'filled' : '' }}" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.5l2.9 6.3 6.9.7-5.2 4.6 1.6 6.8-6.2-3.7-6.2 3.7 1.6-6.8L2.2 9.5l6.9-.7L12 2.5z"/></svg>
                      @endfor
                    </span>
                    <span class="rating-text">
                      @if ($producer->reviews_count > 0)
                        {{ number_format((float) $producer->avg_rating, 1, ',', ' ') }}/5 ({{ $producer->reviews_count }})
                      @else
                        Pas encore d'évaluation
                      @endif
                    </span>
                  </div>
                  @php $singleOffer = $producer->cropOffers->count() === 1 ? $producer->cropOffers->first() : null; @endphp
                  @if ($producer->cropOffers->isNotEmpty())
                    <div class="offer-list">
                      @foreach ($producer->cropOffers as $offer)
                        <p class="offer-line-name">
                          <b>{{ $offer->product_name }}</b>
                          <span class="offer-line-meta">
                            — {{ number_format((float) $offer->quantity, 2, ',', ' ') }} {{ $offer->unit->label() }} disponible
                            @if (! $singleOffer)
                              · {{ $offer->price_indicative ? number_format($offer->price_indicative, 0, ',', ' ').' FCFA' : 'prix à négocier' }}
                            @endif
                          </span>
                        </p>
                      @endforeach
                    </div>
                  @endif

                  {{-- Toujours collé en bas de la carte (margin-top:auto sur .ofoot) —
                       l'action s'aligne donc au même niveau sur toutes les cartes, quel
                       que soit le nombre de lignes du texte au-dessus. --}}
                  <div class="ofoot">
                    @if ($singleOffer)
                      <span class="p">{{ $singleOffer->price_indicative ? number_format($singleOffer->price_indicative, 0, ',', ' ').' FCFA' : 'Prix à négocier' }}</span>
                      @auth
                        @if (auth()->user()->isBuyer())
                          <a href="{{ route('learner.buyer.crop-orders.create', $singleOffer) }}" wire:navigate class="order">Passer ma commande</a>
                        @else
                          <a href="{{ route('learner.buyer') }}" wire:navigate class="order">Passer ma commande</a>
                        @endif
                      @else
                        <a href="{{ route('login') }}" wire:navigate class="order">Passer ma commande</a>
                      @endauth
                    @else
                      <span class="p">{{ $producer->cropOffers->count() }} offres publiées</span>
                      <a href="{{ route('producers.show', $producer) }}" wire:navigate class="order">Voir les offres</a>
                    @endif
                  </div>
                </div>
              </article>
            @endforeach
          </div>

          <div style="margin-top:1rem">
            {{ $producers->links('vendor.pagination.vitrine') }}
          </div>
        @endif
      </div>
    </div>
  </div>
</section>

</main>

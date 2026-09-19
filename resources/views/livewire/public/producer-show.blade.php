<main id="main">
<span id="top"></span>

<section class="section">
  <div class="wrap">
    <a href="{{ route('producers.index') }}" class="back-link">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
      Retour aux producteurs
    </a>

    <div class="producer-profile reveal" wire:ignore.self>
      <div class="producer-profile-photo">
        @if ($producerProfile->isVerified())
          <span class="badge-verified">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
            Vérifié
          </span>
        @endif
        @if ($producerProfile->logo_path)
          <img loading="lazy" decoding="async" src="{{ \Illuminate\Support\Facades\Storage::url($producerProfile->logo_path) }}" alt="{{ $producerProfile->business_name }}">
        @else
          <img loading="lazy" decoding="async" src="{{ asset('img/champ-manioc.jpg') }}" alt="{{ $producerProfile->business_name }}">
        @endif
      </div>

      <div class="producer-profile-info">
        <span class="t">{{ $producerProfile->activity_type->label() }}</span>
        <h1>{{ $producerProfile->business_name }}</h1>
        <p class="loc">{{ $producerProfile->zone }}</p>

        <div class="rating-row">
          <span class="rating-stars" aria-hidden="true">
            @for ($i = 1; $i <= 5; $i++)
              <svg class="{{ $i <= round((float) $averageRating) ? 'filled' : '' }}" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.5l2.9 6.3 6.9.7-5.2 4.6 1.6 6.8-6.2-3.7-6.2 3.7 1.6-6.8L2.2 9.5l6.9-.7L12 2.5z"/></svg>
            @endfor
          </span>
          <span class="rating-text">
            @if ($reviewsCount > 0)
              {{ number_format((float) $averageRating, 1, ',', ' ') }}/5 ({{ $reviewsCount }} collaboration{{ $reviewsCount > 1 ? 's' : '' }})
            @else
              Pas encore d'évaluation
            @endif
          </span>
        </div>

        @if (filled($producerProfile->bio))
          <p style="margin-top:1rem">{{ $producerProfile->bio }}</p>
        @endif

        @if ($producerProfile->years_active || filled($producerProfile->capacity_note))
          <p class="loc" style="margin-top:.6rem">
            @if ($producerProfile->years_active)
              {{ $producerProfile->years_active }} an{{ $producerProfile->years_active > 1 ? 's' : '' }} d'activité
            @endif
            @if ($producerProfile->years_active && filled($producerProfile->capacity_note))
              ·
            @endif
            {{ $producerProfile->capacity_note }}
          </p>
        @endif

        <div style="margin-top:1.3rem">
          @auth
            <form method="POST" action="{{ route('producers.favorite.toggle', $producerProfile) }}">
              @csrf
              <button type="submit" class="btn sm {{ $isFavorite ? '' : 'ghost' }}">
                {{ $isFavorite ? '★ Dans vos favoris' : '☆ Ajouter aux favoris' }}
              </button>
            </form>
          @else
            <a href="{{ route('login') }}" class="btn sm ghost">Se connecter pour ajouter aux favoris</a>
          @endauth
        </div>
      </div>
    </div>

    <div class="catalog-results-head" style="margin-top:2.8rem">
      <p class="catalog-count"><b>{{ $offers->total() }}</b> offre{{ $offers->total() > 1 ? 's' : '' }} publiée{{ $offers->total() > 1 ? 's' : '' }}</p>
    </div>

    @if ($offers->isEmpty())
      <div class="empty-state">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 8l9-5 9 5v8l-9 5-9-5z"/><path d="M3 8l9 5 9-5"/></svg>
        <p>Aucune offre publiée pour le moment.</p>
        <span>Ce producteur n'a pas d'offre disponible en ce moment — revenez plus tard.</span>
      </div>
    @else
      <div class="offers reveal" wire:ignore.self>
        @foreach ($offers as $offer)
          @php $photo = $offer->photos->first(); @endphp
          <article class="offer" wire:key="offer-{{ $offer->id }}">
            <div class="ph">
              @if ($photo)
                <img loading="lazy" decoding="async" src="{{ \Illuminate\Support\Facades\Storage::url($photo->path) }}" alt="{{ $offer->product_name }}">
              @else
                <img loading="lazy" decoding="async" src="{{ asset('img/champ-manioc.jpg') }}" alt="{{ $offer->product_name }}">
              @endif
            </div>
            <div class="ob">
              <span class="t">{{ $offer->unit->label() }}</span>
              <h4>{{ $offer->product_name }}</h4>
              <p class="loc">
                {{ number_format((float) $offer->quantity, 0, ',', ' ') }} {{ $offer->unit->label() }}
                @if (filled($offer->variety)) · {{ $offer->variety }} @endif
              </p>
              <div class="ofoot">
                <span class="p">
                  @if ($offer->price_indicative)
                    {{ number_format($offer->price_indicative, 0, ',', ' ') }} FCFA <small style="font-weight:600">(indicatif)</small>
                  @else
                    Prix à négocier
                  @endif
                </span>
                <a href="{{ route('learner.buyer.offers.contact', $offer) }}" wire:navigate class="order">Négocier</a>
              </div>
              @auth
                @if (auth()->user()->isBuyer())
                  <a href="{{ route('learner.buyer.crop-orders.create', $offer) }}" wire:navigate class="btn sm" style="margin-top:.6rem;width:100%;text-align:center">Passer une commande</a>
                @else
                  <a href="{{ route('learner.buyer') }}" wire:navigate class="btn sm ghost" style="margin-top:.6rem;width:100%;text-align:center">Activer mon espace acheteur pour commander</a>
                @endif
              @else
                <a href="{{ route('login') }}" wire:navigate class="btn sm ghost" style="margin-top:.6rem;width:100%;text-align:center">Se connecter pour commander</a>
              @endauth
            </div>
          </article>
        @endforeach
      </div>

      <div style="margin-top:1rem">
        {{ $offers->links('vendor.pagination.vitrine') }}
      </div>
    @endif
  </div>
</section>

</main>

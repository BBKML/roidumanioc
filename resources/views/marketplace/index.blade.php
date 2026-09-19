@php
    $img = fn ($path, $fallback = 'img/champ-manioc.jpg') => asset($path ?: $fallback);
@endphp
<x-public-layout :title="$section['title'] ?? 'Marketplace'" :description="$section['lead'] ?? null">

<main id="main">
<span id="top"></span>

{{-- Même cadre que /producteurs (bandeau de recherche + sélecteur de mode) : pas de
     Livewire ici, formulaire GET classique (rechargement de page), mais mêmes classes
     visuelles pour que les deux pages se sentent comme une seule expérience. --}}
<section class="search-hero is-first">
  <div class="wrap">
    <x-catalog-mode-tabs active="order" />
    <form class="search-box" method="GET" action="{{ route('marketplace.index') }}">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
      <input type="search" name="q" value="{{ $search }}" placeholder="Rechercher une annonce, un produit…" aria-label="Rechercher">
      @if ($type !== '')
        <input type="hidden" name="type" value="{{ $type }}">
      @endif
      <button type="submit">Rechercher</button>
    </form>
    <div class="search-tabs" role="tablist" aria-label="Type d'annonce">
      <a href="{{ route('marketplace.index', array_filter(['q' => $search])) }}" class="{{ $type === '' ? 'active' : '' }}" role="tab" aria-selected="{{ $type === '' ? 'true' : 'false' }}">Tous</a>
      @foreach ($types as $typeOption)
        <a href="{{ route('marketplace.index', array_filter(['q' => $search, 'type' => $typeOption])) }}" class="{{ $type === $typeOption ? 'active' : '' }}" role="tab" aria-selected="{{ $type === $typeOption ? 'true' : 'false' }}">{{ $typeOption }}</a>
      @endforeach
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="catalog-results">
      <div class="catalog-results-head">
        <p class="catalog-count"><b>{{ $offers->count() }}</b> offre{{ $offers->count() > 1 ? 's' : '' }} trouvée{{ $offers->count() > 1 ? 's' : '' }}</p>
      </div>

      @if ($offers->isEmpty())
        <div class="empty-state">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 8l9-5 9 5v8l-9 5-9-5z"/><path d="M3 8l9 5 9-5"/></svg>
          <p>Aucune offre disponible pour le moment.</p>
          <span>Producteurs et boutique officielle publient régulièrement de nouvelles offres.</span>
        </div>
      @else
        <div class="offers reveal">
          @foreach ($offers as $offer)
            <article class="offer">
              <div class="ph"><img loading="lazy" decoding="async" src="{{ $img($offer['image']) }}" alt="{{ $offer['title'] }}"></div>
              <div class="ob">
                <span class="t">{{ $offer['label'] }}</span>
                <h4>{{ $offer['title'] }}</h4>
                <p class="loc">{{ $offer['location'] }}</p>
                <div class="ofoot">
                  <span class="p">{{ $offer['price'] }}</span>
                  <a href="{{ route($offer['kind'] === 'listing' ? 'learner.listing-checkout' : 'learner.shop-checkout', $offer['id']) }}" class="btn sm">{{ __('site.marketplace.order_button') }}</a>
                </div>
              </div>
            </article>
          @endforeach
        </div>
      @endif
    </div>
  </div>
</section>

</main>

</x-public-layout>

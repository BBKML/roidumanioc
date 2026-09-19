@php
    $img = fn ($path, $fallback = 'img/recolte-village.jpg') => asset($path ?: $fallback);
@endphp
<main id="main">
<span id="top"></span>

{{-- Hero de page (même gabarit que Formations/Marketplace/Producteurs) : cette page n'a plus
     à commencer directement sur un formulaire de filtres — photo réelle + chiffres réels. --}}
<section class="page-hero">
  <div class="wrap">
    <div>
      <p class="eyebrow">{{ $section['eyebrow'] ?? 'Besoins des acheteurs' }}</p>
      <h1>{{ $section['title'] ?? 'Ce que les acheteurs recherchent' }}</h1>
      <p class="lead">{{ $section['lead'] ?? "Producteurs, ces besoins sont peut-être les vôtres à combler. Aucune coordonnée personnelle n'est affichée — la mise en relation se fait via la plateforme." }}</p>
      @if ($openCount > 0 || $zonesCount > 0)
        <div class="page-hero-stats reveal" wire:ignore.self>
          @if ($openCount > 0)
            <div><b class="count-up">{{ $openCount }}</b><span>Besoin{{ $openCount > 1 ? 's' : '' }} ouvert{{ $openCount > 1 ? 's' : '' }}</span></div>
          @endif
          @if ($zonesCount > 0)
            <div><b class="count-up">{{ $zonesCount }}</b><span>Zone{{ $zonesCount > 1 ? 's' : '' }} couverte{{ $zonesCount > 1 ? 's' : '' }}</span></div>
          @endif
        </div>
      @endif
    </div>
    <div class="page-hero-figure">
      <img loading="lazy" decoding="async" src="{{ $img(data_get($section, 'image.src')) }}" alt="{{ data_get($section, 'image.alt', '') }}">
    </div>
  </div>
</section>

{{-- Bandeau de recherche façon page de recherche marketplace, même gabarit que /producteurs. --}}
<section class="search-hero">
  <div class="wrap">
    <form class="search-box" wire:submit.prevent>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
      <input type="search" wire:model.live.debounce.300ms="search" placeholder="Rechercher un produit recherché…" aria-label="Rechercher">
      <button type="submit">Rechercher</button>
    </form>
  </div>
</section>

<section class="section">
  <div class="wrap">
    {{-- Même mise en page que /producteurs (filtres en barre latérale sticky + résultats en
         grille) pour une expérience cohérente entre les deux catalogues de mise en relation. --}}
    <div class="catalog-layout">
      <aside class="catalog-filters">
        <div class="catalog-filters-head">
          <h2>Affiner</h2>
          @if ($search !== '' || $zone !== '' || $product !== '')
            <button type="button" wire:click="resetFilters">Réinitialiser</button>
          @endif
        </div>
        <form class="filters" wire:submit.prevent>
          <div class="filter-group">
            <label for="f-product">Produit recherché</label>
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
        </form>
      </aside>

      <div class="catalog-results">
        <div class="catalog-results-head">
          <p class="catalog-count"><b>{{ $needs->total() }}</b> besoin{{ $needs->total() > 1 ? 's' : '' }} trouvé{{ $needs->total() > 1 ? 's' : '' }}</p>
        </div>

        @if ($search !== '' || $zone !== '' || $product !== '')
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
          </div>
        @endif

        @if ($needs->isEmpty())
          <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
            <p>Aucun besoin ne correspond à ces critères.</p>
            <span>Essayez d'élargir votre recherche ou de retirer un filtre.</span>
          </div>
        @else
          <div class="offers reveal" wire:ignore.self>
            @foreach ($needs as $need)
              <article class="offer" wire:key="need-{{ $need->id }}">
                <div class="ob">
                  <span class="t">{{ $need->frequency === 'recurrent' ? 'Besoin récurrent' : 'Besoin ponctuel' }}</span>
                  <h4>{{ $need->product_wanted }}</h4>
                  <p class="loc">
                    {{ number_format((float) $need->quantity, 2, ',', ' ') }} {{ $need->unit->label() }}
                    · {{ $need->location }}
                  </p>
                  @if ($need->buyerProfile?->company_name)
                    <p class="loc" style="margin-top:.3rem">{{ $need->buyerProfile->company_name }}</p>
                  @endif
                  @if ($need->wanted_date)
                    <p class="loc" style="margin-top:.3rem">Souhaité pour le {{ $need->wanted_date->translatedFormat('d F Y') }}</p>
                  @endif
                  <div class="ofoot">
                    <span class="p">{{ $need->budget_indicative ? number_format($need->budget_indicative, 0, ',', ' ').' FCFA' : 'Budget à convenir' }}</span>
                    <a href="{{ route('learner.producer.needs.respond', $need) }}" wire:navigate class="order">Répondre</a>
                  </div>
                </div>
              </article>
            @endforeach
          </div>

          <div style="margin-top:1rem">
            {{ $needs->links('vendor.pagination.vitrine') }}
          </div>
        @endif
      </div>
    </div>
  </div>
</section>

</main>

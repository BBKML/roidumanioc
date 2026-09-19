@php
    $img = fn ($path, $fallback = 'img/champ-manioc.jpg') => asset($path ?: $fallback);
@endphp
<x-public-layout :title="$content['name'] ?? 'Le fondateur'" :description="$content['quote'] ?? null">

<main id="main">
<span id="top"></span>

<section class="founder">
  <div class="wrap">
    <div class="founder-figure">
      <img loading="lazy" decoding="async" src="{{ $img(data_get($content, 'image.src'), 'img/fondateur-conference.jpg') }}" alt="{{ data_get($content, 'image.alt', '') }}">
    </div>
    <div>
      <p class="eyebrow">{{ $content['eyebrow'] ?? 'Le fondateur' }}</p>
      <blockquote>« {{ $content['quote'] ?? '' }} »</blockquote>
      <h1 class="sig" style="font-size:1.3rem;margin-top:.4rem">{{ $content['name'] ?? '' }}</h1>
      <p class="role">{{ $content['role'] ?? '' }}</p>
      <div class="hero-actions" style="margin-top:1.8rem">
        <a href="{{ route('formations.index') }}" class="btn on-dark ghost">Découvrir ses formations</a>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="awards reveal">
      <div>
        <p class="eyebrow">{{ $distinctions['eyebrow'] ?? 'Reconnaissance' }}</p>
        <h2>{{ $distinctions['title'] ?? 'Un travail salué au plus haut niveau.' }}</h2>
        <div class="award-list">
          @forelse ($awards as $award)
            <div class="award">
              <span class="yr">{{ $award->year }}</span>
              <div><b>{{ $award->localized('title') }}</b><p>{{ $award->localized('description') }}</p></div>
            </div>
          @empty
            <div class="empty-state" style="text-align:left">
              <p>Aucune distinction pour le moment.</p>
              <span>Les prochaines reconnaissances seront affichées ici.</span>
            </div>
          @endforelse
        </div>
      </div>
      <div class="award-figure">
        <img loading="lazy" decoding="async" src="{{ $img(data_get($distinctions, 'image.src'), 'img/ceremonie-attestations.jpg') }}" alt="{{ data_get($distinctions, 'image.alt', '') }}">
      </div>
    </div>
  </div>
</section>

</main>

</x-public-layout>

@php
    $img = fn ($path, $fallback = 'img/champ-manioc.jpg') => asset($path ?: $fallback);
@endphp
<x-public-layout :title="$content['title'] ?? 'Placali du Roi'" :description="$content['text'] ?? null">

<main id="main">
<span id="top"></span>

<section class="placali">
  <div class="wrap">
    <div class="placali-copy reveal">
      <p class="eyebrow">{{ $content['eyebrow'] ?? 'Le produit signature' }}</p>
      <h1>{{ $content['title'] ?? 'Placali du Roi' }}</h1>
      <p class="lead">{{ $content['text'] ?? '' }}</p>
      <div class="placali-tags">
        @foreach (($content['atouts'] ?? []) as $atout)
          <span>{{ $atout }}</span>
        @endforeach
      </div>
      <div class="placali-price">
        <b>{{ $content['price'] ?? '' }}</b><span>{{ $content['price_detail'] ?? '' }}</span>
      </div>
    </div>
    <div class="placali-pack reveal">
      <div class="seal">{!! __('site.placali.seal') !!}</div>
      <img loading="lazy" decoding="async" src="{{ $img(data_get($content, 'image_product.src'), 'img/placali-etal.jpg') }}" alt="{{ data_get($content, 'image_product.alt', '') }}">
    </div>
  </div>
</section>

</main>

</x-public-layout>

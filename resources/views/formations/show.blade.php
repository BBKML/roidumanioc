@php
    $img = fn ($path, $fallback = 'img/champ-manioc.jpg') => asset($path ?: $fallback);
    $fcfa = fn ($n) => number_format((int) $n, 0, ',', ' ').' FCFA';
@endphp
<x-public-layout :title="$formation->title" :description="\Illuminate\Support\Str::limit($formation->description, 155)">

<main id="main">
<span id="top"></span>

<section class="section">
  <div class="wrap">
    <div class="mission">
      <div>
        <p class="eyebrow">{{ $formation->category ?: 'Formation' }}</p>
        <h1>{{ $formation->title }}</h1>
        <p class="lead" style="margin:.6rem 0 1.2rem">{{ $formation->description }}</p>
        <div class="hero-trust" style="margin-bottom:1.6rem">
          <span class="dot"></span><span>{{ $formation->lessons_count }} {{ $formation->lessons_count > 1 ? __('site.formation.lessons_plural') : __('site.formation.lessons_singular') }}</span>
          @if ($formation->duration_label)
            <span class="dot"></span><span>{{ $formation->duration_label }}</span>
          @endif
          <span class="dot"></span><span>{{ $formation->isFree() ? __('site.formation.free_tag') : $fcfa($formation->price) }}</span>
        </div>
        @auth
          <a href="{{ route('learner.course', $formation) }}" class="btn gold">{{ __('site.formation.access_button') }}</a>
        @else
          {{-- Lien direct vers la ressource protégée (pas /login) : le middleware auth
               mémorise cette URL et redirect()->intended() y ramène l'invité une fois
               connecté ou inscrit, plutôt que sur le tableau de bord générique. --}}
          @if ($formation->isFree())
            <a href="{{ route('learner.course', $formation) }}" class="btn gold">Créer mon compte gratuit</a>
          @else
            <a href="{{ route('learner.checkout', $formation) }}" class="btn gold">S'inscrire à cette formation</a>
          @endif
        @endauth
        <a href="{{ route('formations.index') }}" class="btn ghost" style="margin-left:.6rem">← Toutes les formations</a>
      </div>
      <div class="mission-figure">
        <img loading="lazy" decoding="async" src="{{ $img($formation->image_path) }}" alt="{{ $formation->title }}">
      </div>
    </div>
  </div>
</section>

</main>

</x-public-layout>

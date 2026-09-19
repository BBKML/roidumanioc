@php
    $img = fn ($path, $fallback = 'img/champ-manioc.jpg') => asset($path ?: $fallback);
    $fcfa = fn ($n) => number_format((int) $n, 0, ',', ' ').' FCFA';
    $freeCount = $formations->filter(fn ($f) => $f->isFree())->count();
@endphp
<x-public-layout :title="$section['title'] ?? 'Formations'" :description="$section['lead'] ?? null">

<main id="main">
<span id="top"></span>

<section class="page-hero">
  <div class="wrap">
    <div>
      <p class="eyebrow">{{ $section['eyebrow'] ?? 'Formations' }}</p>
      <h1>{{ $section['title'] ?? 'Apprendre le manioc, sérieusement.' }}</h1>
      <p class="lead">{{ $section['lead'] ?? '' }}</p>
      <div class="hero-actions">
        <a href="#catalogue" class="btn">Voir le catalogue</a>
        <a href="{{ route('register') }}" class="btn ghost">Créer mon compte gratuit</a>
        <a href="{{ route('events.index') }}" class="btn ghost">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
          Formations en présentiel (événements)
        </a>
      </div>
      @if ($formations->count())
        <div class="page-hero-stats reveal">
          <div><b class="count-up">{{ $formations->count() }}</b><span>Formation{{ $formations->count() > 1 ? 's' : '' }} au catalogue</span></div>
          @if ($freeCount)
            <div><b class="count-up">{{ $freeCount }}</b><span>Gratuite{{ $freeCount > 1 ? 's' : '' }} pour débuter</span></div>
          @endif
        </div>
      @endif
    </div>
    <div class="page-hero-figure">
      <img loading="lazy" decoding="async" src="{{ $img(data_get($section, 'image.src'), 'img/formation-champ.jpg') }}" alt="{{ data_get($section, 'image.alt', '') }}">
    </div>
  </div>
</section>

{{-- Distinctions réelles obtenues par le formateur (modèle Award, même source que
     /fondateur) — masqué tant qu'aucune n'est publiée, jamais un bandeau vide. --}}
@if ($awards->isNotEmpty())
<section class="awards-strip reveal" aria-label="Distinctions">
  <div class="wrap awards-strip-list">
    @foreach ($awards as $award)
      <div class="awards-strip-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="5"/><path d="M8.5 12.5 7 22l5-3 5 3-1.5-9.5"/></svg>
        <span>@if($award->year)<b>{{ $award->year }}</b> — @endif{{ $award->localized('title') }}</span>
      </div>
    @endforeach
  </div>
</section>
@endif

<section class="section" id="catalogue">
  <div class="wrap">
    <div class="courses reveal">
      @forelse ($formations as $formation)
        <a href="{{ route('formations.show', $formation) }}" class="course" style="text-decoration:none;color:inherit">
          <div class="ph">
            <img loading="lazy" decoding="async" src="{{ $img($formation->image_path) }}" alt="{{ $formation->title }}">
            <span class="tag{{ $formation->isFree() ? ' free' : '' }}">{{ $formation->isFree() ? __('site.formation.free_tag') : __('site.formation.premium_tag') }}</span>
          </div>
          <div class="cb">
            <h3>{{ $formation->title }}</h3>
            <div class="meta">
              <span>{{ $formation->lessons_count }} {{ $formation->lessons_count > 1 ? __('site.formation.lessons_plural') : __('site.formation.lessons_singular') }}</span>
              @if ($formation->duration_label)<span>{{ $formation->duration_label }}</span>@endif
            </div>
            <span class="price{{ $formation->isFree() ? ' free' : '' }}">{{ $formation->isFree() ? __('site.formation.free_tag') : $fcfa($formation->price) }}</span>
          </div>
        </a>
      @empty
        <div class="empty-state">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4v16l7-2 7 2V4l-7 2-7-2z"/></svg>
          <p>Aucune formation publiée pour le moment.</p>
          <span>De nouveaux parcours arrivent bientôt — revenez faire un tour.</span>
        </div>
      @endforelse
    </div>
  </div>
</section>

</main>

</x-public-layout>

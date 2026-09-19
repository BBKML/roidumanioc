@php
    $section = \App\Models\SiteContent::section('evenements_section', []);
    $img = fn ($path, $fallback = 'img/ceremonie-attestations.jpg') => asset($path ?: $fallback);
    // Le titre du hero peut contenir <br>/<em> (champ CMS "html") : jamais utilisé tel
    // quel dans <title>/meta, qui doivent rester du texte brut.
    $titlePlain = trim(strip_tags(str_replace('<br>', ' ', $section['title'] ?? 'Les prochains rendez-vous du royaume.')));

    // Pas de dépendance à l'extension intl (souvent absente/incomplète sous Windows) :
    // les noms de jours/mois sont mappés à la main plutôt que via Carbon::translatedFormat().
    $joursFull = [0 => 'Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
    $moisFull = [1 => 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
    $fullDate = fn ($d) => $joursFull[(int) $d->format('w')].' '.$d->format('d').' '.$moisFull[(int) $d->format('n')].' '.$d->format('Y');
    // Date relative en texte ("Dans 3 jours") plutôt que le compte à rebours chiffré du
    // hero — plus sobre pour un format éditorial répété sur chaque carte de la liste.
    $relativeDays = function ($date) {
        $days = (int) now()->startOfDay()->diffInDays($date->copy()->startOfDay());

        return match (true) {
            $days === 0 => "Aujourd'hui",
            $days === 1 => 'Demain',
            $days < 7 => "Dans {$days} jours",
            $days < 31 => 'Dans '.intdiv($days, 7).' semaine'.(intdiv($days, 7) > 1 ? 's' : ''),
            default => 'Dans '.intdiv($days, 30).' mois',
        };
    };
@endphp
<x-public-layout :title="$titlePlain" :description="$section['lead'] ?? null">

<main id="main">
<span id="top"></span>

{{-- Hero distinct des autres pages : fil d'ariane + badge pilule + titre bicolore sur
     photo plein cadre à bord diagonal (même esprit que la carte "vraie donnée" du
     Marketplace : le prochain événement réel reste visible via le petit compte à
     rebours sous les boutons, plutôt que dans un grand panneau séparé). --}}
<section class="events-hero">
  <img class="events-hero-bg" src="{{ $img(data_get($section, 'image.src')) }}" alt="" fetchpriority="high">
  <div class="events-hero-scrim" aria-hidden="true"></div>
  <div class="events-hero-content">
    <nav class="events-breadcrumb" aria-label="Fil d'ariane">
      <a href="{{ route('home') }}">Accueil</a><span>›</span><span>Événements</span>
    </nav>
    <span class="events-pill">{{ $section['eyebrow'] ?? 'Événements & rencontres' }}</span>
    <h1>{!! $section['title'] ?? 'Les prochains<br><em>rendez-vous</em> du royaume.' !!}</h1>
    <p class="lead">{{ $section['lead'] ?? '' }}</p>
    <div class="hero-actions">
      <a href="#evenements" class="btn">Voir les événements</a>
      <a href="{{ route('contact') }}" class="btn on-dark ghost">Nous contacter</a>
    </div>
    @if ($spotlight && $spotlight->starts_at && $spotlight->starts_at->isFuture())
      <div class="events-hero-countdown" data-event-deadline="{{ $spotlight->starts_at->toIso8601String() }}">
        <span class="events-hero-countdown-label">⏳ Prochain événement : {{ $spotlight->title }}</span>
        <div class="event-countdown">
          <div><b data-unit="days">00</b><span>Jours</span></div>
          <div><b data-unit="hours">00</b><span>Heures</span></div>
          <div><b data-unit="minutes">00</b><span>Min</span></div>
        </div>
        <p class="event-countdown-ended" hidden>📍 C'est aujourd'hui !</p>
      </div>
    @elseif ($events->count())
      <p class="events-hero-countdown-label" style="margin-top:.5rem;color:rgba(246,242,230,.7);font-size:.85rem">
        <b class="count-up" style="color:var(--gold-soft)">{{ $events->count() }}</b> événement{{ $events->count() > 1 ? 's' : '' }} à venir
      </p>
    @endif
  </div>
</section>

<section class="section" id="evenements">
  <div class="wrap">
    <div class="events-list reveal">
      @forelse ($events as $event)
        <article class="event-row">
          <div class="event-row-media">
            <img loading="lazy" decoding="async" src="{{ $event->image_path ? \Illuminate\Support\Facades\Storage::url($event->image_path) : $img(null) }}" alt="{{ $event->title }}">
            <span class="event-row-badge">{{ $event->type }}</span>
          </div>
          <div class="event-row-body">
            @if ($event->date_label || $event->starts_at)
              <p class="event-row-date">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                <span>{{ $event->date_label ?: $fullDate($event->starts_at) }}</span>
                @if ($event->starts_at && $event->starts_at->isFuture())
                  <span class="rel">· {{ $relativeDays($event->starts_at) }}</span>
                @endif
              </p>
            @endif
            <h3>{{ $event->title }}</h3>
            @if ($event->description)
              <p class="event-row-desc">{{ \Illuminate\Support\Str::limit($event->description, 160) }}</p>
            @endif
            <div class="event-row-foot">
              <div class="event-row-org">
                <img src="{{ asset('img/logo.png') }}" alt="">
                <div><b>Le Roi du Manioc</b><span>{{ $event->type }}</span></div>
              </div>
              <a href="{{ $event->link ?: route('contact') }}" @if ($event->link) target="_blank" rel="noopener" @endif class="btn gold">
                Plus d'infos
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
              </a>
            </div>
          </div>
        </article>
      @empty
        <div class="empty-state">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
          <p>Aucun événement à venir pour le moment.</p>
          <span>De nouvelles activités seront bientôt annoncées — revenez faire un tour.</span>
        </div>
      @endforelse
    </div>
  </div>
</section>

</main>

</x-public-layout>

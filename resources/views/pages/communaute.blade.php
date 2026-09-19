@php
    $img = fn ($path, $fallback = 'img/champ-manioc.jpg') => asset($path ?: $fallback);
    $initials = fn ($name) => \Illuminate\Support\Str::of($name)
        ->explode(' ')
        ->filter()
        ->map(fn ($w) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($w, 0, 1)))
        ->take(2)
        ->implode('');
@endphp
<x-public-layout :title="$content['title'] ?? 'La communauté'" :description="$content['lead'] ?? null">

<main id="main">
<span id="top"></span>

<section class="page-hero">
  <div class="wrap">
    <div>
      <p class="eyebrow">{{ $content['eyebrow'] ?? 'La communauté' }}</p>
      <h1>{{ $content['title'] ?? 'On avance mieux ensemble.' }}</h1>
      <p class="lead">{{ $content['lead'] ?? '' }}</p>
      <div class="hero-actions">
        <a href="{{ route('register') }}" class="btn gold">Rejoindre le royaume</a>
        <a href="#temoignages" class="btn ghost">Lire les témoignages</a>
      </div>
    </div>
    <div class="page-hero-figure">
      <img loading="lazy" decoding="async" src="{{ $img(data_get($content, 'image.src'), 'img/communaute-champ.jpg') }}" alt="{{ data_get($content, 'image.alt', '') }}">
    </div>
  </div>
</section>

{{-- Logos partenaires réels (modèle dédié), défilement continu — masqué tant qu'aucun
     n'est publié avec un logo. Dupliqué une fois dans le DOM pour un défilement sans
     coupure (technique CSS pure) ; sous prefers-reduced-motion:reduce, l'animation est
     coupée et le second jeu masqué (cf. vitrine.css), affichant une simple rangée fixe. --}}
@if ($partners->isNotEmpty())
<section class="partners-strip" aria-label="Nos partenaires">
  <div class="wrap"><p class="eyebrow center">Ils nous accompagnent</p></div>
  <div class="partners-marquee">
    <div class="partners-track">
      {{-- Pas de loading="lazy" ici : un ancêtre en boucle avec transform (le défilement)
           perturbe le calcul d'intersection du navigateur pour le chargement différé, ce
           qui peut faire échouer silencieusement le chargement de certains logos. La
           liste reste courte (quelques logos), le chargement immédiat ne coûte rien. --}}
      <div class="partners-set">
        @foreach ($partners as $partner)
          <img src="{{ \Illuminate\Support\Facades\Storage::url($partner->logo_path) }}" alt="{{ $partner->name }}">
        @endforeach
      </div>
      <div class="partners-set" aria-hidden="true">
        @foreach ($partners as $partner)
          <img src="{{ \Illuminate\Support\Facades\Storage::url($partner->logo_path) }}" alt="">
        @endforeach
      </div>
    </div>
  </div>
</section>
@endif

<section class="section" id="temoignages">
  <div class="section-head reveal">
    <p class="eyebrow">Témoignages</p>
    <h2>Ce qu'en dit la communauté.</h2>
    {{-- Clarifie la différence entre cette page (publique, lecture seule) et le forum
         réservé aux membres (/mon-espace/communaute) — sans quoi le mot "communauté"
         désigne deux choses différentes sans qu'on le sache. --}}
    <p class="lead">Témoignages publics, visibles par tous. Une fois votre compte créé, retrouvez aussi le forum de la communauté dans votre espace membre pour poser vos questions et échanger.</p>
  </div>
  <div class="wrap">
    <div class="community-grid reveal">
      @forelse ($testimonials as $t)
        <figure class="quote">
          <p>« {{ $t->localized('quote') }} »</p>
          <figcaption class="who">
            <span class="av">{{ $initials($t->author_name) }}</span>
            <span class="who-name"><b>{{ $t->author_name }}</b><span>{{ $t->localized('author_role') }}</span></span>
          </figcaption>
        </figure>
      @empty
        <div class="empty-state">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
          <p>Aucun témoignage pour le moment.</p>
          <span>Les premiers retours de la communauté arrivent bientôt.</span>
        </div>
      @endforelse
    </div>
  </div>
</section>

</main>

</x-public-layout>

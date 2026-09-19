{{-- Vitrine — portée de la maquette index.html (design validé). 100 % base de données. --}}
<x-public-layout>

@php
    $hero        = $content['hero'] ?? [];
    $bandeau     = $content['bandeau'] ?? [];
    $mission     = $content['mission'] ?? [];
    $chiffres    = $content['chiffres']['items'] ?? [];
    $piliers     = $content['piliers'] ?? [];
    $placali     = $content['placali'] ?? [];
    $fSection    = $content['formations_section'] ?? [];
    $mSection    = $content['marketplace_section'] ?? [];
    $pSection    = $content['producteurs_section'] ?? [];
    $communaute  = $content['communaute'] ?? [];
    $distinctions = $content['distinctions'] ?? [];
    $fondateur   = $content['fondateur'] ?? [];
    $ctaFinal    = $content['cta'] ?? [];

    $img = fn ($path, $fallback = 'img/champ-manioc.jpg') => asset($path ?: $fallback);
    $fcfa = fn ($n) => number_format((int) $n, 0, ',', ' ').' FCFA';
    $initials = fn ($name) => \Illuminate\Support\Str::of($name)
        ->explode(' ')
        ->filter()
        ->map(fn ($w) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($w, 0, 1)))
        ->take(2)
        ->implode('');

    // Liens fixes des 4 piliers (structure de navigation, pas du contenu éditorial).
    $piliersLinks = [
        [route('formations.index'), __('site.pillar_links.formations')],
        [route('marketplace.index'), __('site.pillar_links.marketplace')],
        [route('marketplace.index'), __('site.pillar_links.shop')],
        [route('placali.show'), __('site.pillar_links.placali')],
    ];
@endphp

<main id="main">
<span id="top"></span>

<section class="hero">
  <img class="hero-bg" src="{{ $img(data_get($hero, 'image.src')) }}" alt="{{ data_get($hero, 'image.alt', '') }}" fetchpriority="high">
  <div class="hero-scrim" aria-hidden="true"></div>
  <div class="wrap hero-content">
    <p class="eyebrow">{{ $hero['eyebrow'] ?? '' }}</p>
    <h1>{!! $hero['title_html'] ?? e($hero['title'] ?? '') !!}</h1>
    <p class="lead">{{ $hero['text'] ?? '' }}</p>
    <div class="hero-actions">
      <a href="#placali" class="btn">{{ $hero['button1'] ?? 'Découvrir le Placali du Roi' }}</a>
      {{-- Vers le catalogue public (consultable sans compte), pas /login : "Accéder aux
           formations" doit vraiment montrer les formations, pas demander de se connecter
           avant même de les avoir vues. --}}
      <a href="{{ route('formations.index') }}" class="btn on-dark ghost">{{ $hero['button2'] ?? 'Accéder aux formations' }}</a>
    </div>
    <div class="hero-trust">
      @foreach (($hero['trust'] ?? []) as $item)
        <span class="dot"></span><span>{{ $item }}</span>
      @endforeach
    </div>
  </div>
</section>

<section class="press" aria-label="Distinctions et partenaires">
  <div class="wrap">
    <span>{{ __('site.press.trusted_by') }}</span>
    @foreach (($bandeau['items'] ?? []) as $item)
      <span>{{ $item }}</span>
    @endforeach
  </div>
</section>

<section class="section" id="mission">
  <div class="wrap">
    <div class="mission">
      <div>
        <p class="eyebrow">{{ $mission['eyebrow'] ?? '' }}</p>
        <h2>{{ $mission['title'] ?? '' }}</h2>
        <p>{{ $mission['p1'] ?? '' }}</p>
        <p>{{ $mission['p2'] ?? '' }}</p>
        <p class="pull">« {{ $mission['quote'] ?? '' }} »</p>
      </div>
      <div class="mission-figure">
        <img loading="lazy" decoding="async" src="{{ $img(data_get($mission, 'image.src'), 'img/producteur-agent.jpg') }}" alt="{{ data_get($mission, 'image.alt', '') }}">
      </div>
    </div>
  </div>
</section>

<section class="stats reveal" aria-label="Le royaume en chiffres">
  <div class="wrap">
    <div class="stats-grid">
      @foreach ($chiffres as $stat)
        <div class="stat"><b class="count-up">{{ $stat['value'] ?? '' }}</b><span>{{ $stat['label'] ?? '' }}</span></div>
      @endforeach
    </div>
  </div>
</section>

<section class="section" id="piliers">
  <div class="section-head center reveal">
    <p class="eyebrow center">{{ $piliers['eyebrow'] ?? '' }}</p>
    <h2>{{ $piliers['title'] ?? '' }}</h2>
    <p class="lead">{{ $piliers['lead'] ?? '' }}</p>
  </div>
  <div class="wrap">
    <div class="pillars reveal">
      @foreach (($piliers['cards'] ?? []) as $i => $card)
        <article class="pillar">
          <div class="ph"><img loading="lazy" decoding="async" src="{{ $img(data_get($card, 'image.src')) }}" alt="{{ data_get($card, 'image.alt', '') }}"></div>
          <div class="pb">
            <span class="num">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
            <h3>{{ $card['title'] ?? '' }}</h3>
            <p>{{ $card['text'] ?? '' }}</p>
            <a href="{{ $piliersLinks[$i][0] ?? '#' }}" class="go">{{ $piliersLinks[$i][1] ?? __('site.pillar_links.default') }}</a>
          </div>
        </article>
      @endforeach
    </div>
  </div>
</section>

<section class="placali" id="placali">
  <div class="wrap">
    <div class="placali-copy reveal">
      <p class="eyebrow">{{ $placali['eyebrow'] ?? '' }}</p>
      <h2>{{ $placali['title'] ?? 'Placali du Roi' }}</h2>
      {{-- Accroche complète réservée à la page dédiée /placali (pas de duplication mot
           pour mot) — même logique que les mini-sections Formations/Marketplace/
           Communauté ci-dessous. --}}
      <div class="placali-tags">
        @foreach (($placali['atouts'] ?? []) as $atout)
          <span>{{ $atout }}</span>
        @endforeach
      </div>
      <div class="placali-price">
        <b>{{ $placali['price'] ?? '' }}</b><span>{{ $placali['price_detail'] ?? '' }}</span>
      </div>
      <div class="hero-actions">
        {{-- Aucun checkout Placali n'existe (Phase 3, audit UX) : plus de fausse promesse
             de commande en ligne — mène vers la page dédiée. Un seul bouton ici (Phase 4) :
             l'ancien second bouton (site.placali.learn_more_button) faisait doublon exact
             (même texte, même destination) avec celui-ci une fois la Phase 3 appliquée. --}}
        <a href="{{ route('placali.show') }}" class="btn gold">{{ $placali['button'] ?? 'Découvrir le Placali du Roi' }}</a>
      </div>
    </div>
    <div class="placali-pack reveal">
      <div class="seal">{!! __('site.placali.seal') !!}</div>
      <img loading="lazy" decoding="async" src="{{ $img(data_get($placali, 'image_product.src'), 'img/placali-etal.jpg') }}" alt="{{ data_get($placali, 'image_product.alt', '') }}">
    </div>
  </div>
</section>

<section class="section" id="formations">
  {{-- Le lead (court, une phrase) est réaffiché ici : un visiteur qui descend la page
       doit comprendre en un coup d'œil ce qu'est cet espace avant d'arriver au suivant
       (Marketplace) — sans quoi les deux se confondent. Même phrase que /formations,
       assumé : la clarté prime ici sur la non-duplication. --}}
  <div class="section-head reveal">
    <p class="eyebrow">{{ $fSection['eyebrow'] ?? 'Formations' }}</p>
    <h2>{{ $fSection['title'] ?? '' }}</h2>
    @if (! empty($fSection['lead']))
      <p class="lead">{{ $fSection['lead'] }}</p>
    @endif
  </div>
  <div class="wrap">
    <div class="courses reveal">
      @foreach ($formations as $formation)
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
      @endforeach
    </div>
    <div class="market-foot reveal">
      <a href="{{ route('formations.index') }}" class="btn">{{ __('site.formation.see_all_button') }}</a>
    </div>
  </div>
</section>

<section class="section market" id="marketplace">
  {{-- Même logique que Formations ci-dessus : le lead est réaffiché pour distinguer
       clairement "acheter/vendre des produits déjà en vente" (ici) de "publier une
       annonce/un besoin entre particuliers" (section Producteurs & acheteurs plus bas). --}}
  <div class="section-head reveal">
    <p class="eyebrow">{{ $mSection['eyebrow'] ?? 'Marketplace' }}</p>
    <h2>{{ $mSection['title'] ?? '' }}</h2>
    @if (! empty($mSection['lead']))
      <p class="lead">{{ $mSection['lead'] }}</p>
    @endif
  </div>
  <div class="wrap">
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
              {{-- Vers la vraie fiche de commande (pas /login) : redirect()->intended()
                   y ramène l'invité une fois connecté/inscrit. --}}
              <a href="{{ route($offer['kind'] === 'listing' ? 'learner.listing-checkout' : 'learner.shop-checkout', $offer['id']) }}" class="btn sm">{{ __('site.marketplace.order_button') }}</a>
            </div>
          </div>
        </article>
      @endforeach
    </div>
    <div class="market-foot reveal">
      <a href="{{ route('marketplace.index') }}" class="btn">{{ __('site.marketplace.see_all_button') }}</a>
    </div>
  </div>
</section>

{{-- Mise en relation producteurs/acheteurs (§14 — le cœur de la V1) : traitement à part,
     volontairement différent du moule "section blanche générique" des autres mini-sections
     (fond sombre + accent doré, vraie photo, chiffres réels en gros format) pour que cette
     fonctionnalité ait enfin un vrai poids visuel à l'accueil, pas seulement un paragraphe. --}}
<section class="connect" id="producteurs">
  <div class="wrap">
    <div class="connect-grid">
      <div class="connect-copy reveal">
        <p class="eyebrow">{{ $pSection['eyebrow'] ?? 'Producteurs & acheteurs' }}</p>
        <h2>{{ $pSection['title'] ?? '' }}</h2>
        <p class="lead">{{ $pSection['lead'] ?? '' }}</p>
        <ul class="opportunity-list">
          @foreach (($pSection['opportunities'] ?? []) as $opportunity)
            <li>
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
              <span>{{ $opportunity }}</span>
            </li>
          @endforeach
        </ul>
        {{-- §8.1 : indicateur communauté tiré des vraies données, déplacé ici (pas dupliqué)
             depuis la section Communauté — il parle directement de mise en relation, sa vraie
             place. Masqué tant que les deux compteurs sont à zéro, comme avant. --}}
        @if ($verifiedProducersCount > 0 || $completedCollaborationsCount > 0)
          <p class="connect-stat">
            <b class="count-up">{{ $verifiedProducersCount }}</b> producteur{{ $verifiedProducersCount > 1 ? 's' : '' }} vérifié{{ $verifiedProducersCount > 1 ? 's' : '' }}
            · <b class="count-up">{{ $completedCollaborationsCount }}</b> collaboration{{ $completedCollaborationsCount > 1 ? 's' : '' }} terminée{{ $completedCollaborationsCount > 1 ? 's' : '' }}
          </p>
        @endif
        <p class="lead">{{ $pSection['relation_text'] ?? '' }}</p>
        <div class="hero-actions">
          <a href="{{ route('learner.producer') }}" class="btn gold">{{ $pSection['button_producer'] ?? 'Devenir producteur' }}</a>
          <a href="{{ route('learner.buyer') }}" class="btn on-dark ghost">{{ $pSection['button_buyer'] ?? 'Devenir acheteur' }}</a>
        </div>
        <div class="connect-links">
          <a href="{{ route('producers.index') }}">{{ __('site.producteurs.see_producers_button') }} →</a>
          <a href="{{ route('needs.index') }}">{{ __('site.producteurs.see_needs_button') }} →</a>
        </div>
      </div>
      <div class="connect-figure reveal">
        <img loading="lazy" decoding="async" src="{{ $img(data_get($pSection, 'image.src'), 'img/recolte-village.jpg') }}" alt="{{ data_get($pSection, 'image.alt', '') }}">
        <div class="connect-badge">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>
          <div><b>Producteur vérifié</b><span>Identité confirmée par l'équipe</span></div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section" id="communaute">
  {{-- Pas de lead ici non plus (texte complet sur /communaute). L'indicateur chiffré
       (producteurs vérifiés / collaborations terminées) vit désormais dans la section
       « Producteurs & acheteurs » ci-dessus — sa vraie place, pas ici. --}}
  <div class="section-head reveal">
    <p class="eyebrow">{{ $communaute['eyebrow'] ?? 'La communauté' }}</p>
    <h2>{{ $communaute['title'] ?? '' }}</h2>
  </div>
  <div class="wrap">
    <div class="community-grid reveal">
      @foreach ($testimonials as $t)
        <figure class="quote">
          <p>« {{ $t->localized('quote') }} »</p>
          <figcaption class="who">
            <span class="av">{{ $initials($t->author_name) }}</span>
            <span class="who-name"><b>{{ $t->author_name }}</b><span>{{ $t->localized('author_role') }}</span></span>
          </figcaption>
        </figure>
      @endforeach
    </div>
    <div class="community-photo reveal">
      <img loading="lazy" decoding="async" src="{{ $img(data_get($communaute, 'image.src'), 'img/communaute-champ.jpg') }}" alt="{{ data_get($communaute, 'image.alt', '') }}">
    </div>
  </div>
</section>

<section class="section" id="distinctions">
  <div class="wrap">
    <div class="awards reveal">
      <div>
        <p class="eyebrow">{{ $distinctions['eyebrow'] ?? 'Reconnaissance' }}</p>
        <h2>{{ $distinctions['title'] ?? '' }}</h2>
        <div class="award-list">
          @foreach ($awards as $award)
            <div class="award">
              <span class="yr">{{ $award->year }}</span>
              <div><b>{{ $award->localized('title') }}</b><p>{{ $award->localized('description') }}</p></div>
            </div>
          @endforeach
        </div>
      </div>
      <div class="award-figure">
        <img loading="lazy" decoding="async" src="{{ $img(data_get($distinctions, 'image.src'), 'img/ceremonie-attestations.jpg') }}" alt="{{ data_get($distinctions, 'image.alt', '') }}">
      </div>
    </div>
  </div>
</section>

<section class="founder" id="fondateur">
  <div class="wrap">
    <div class="founder-figure">
      <img loading="lazy" decoding="async" src="{{ $img(data_get($fondateur, 'image.src'), 'img/fondateur-conference.jpg') }}" alt="{{ data_get($fondateur, 'image.alt', '') }}">
    </div>
    <div>
      <p class="eyebrow">{{ $fondateur['eyebrow'] ?? 'Le fondateur' }}</p>
      {{-- Citation raccourcie ici : la citation complète vit sur la page dédiée
           /fondateur (pas de duplication mot pour mot d'une page à l'autre). --}}
      <blockquote>« {{ \Illuminate\Support\Str::limit($fondateur['quote'] ?? '', 110) }} »</blockquote>
      <p class="sig">{{ $fondateur['name'] ?? '' }}</p>
      <p class="role">{{ $fondateur['role'] ?? '' }}</p>
      <div class="hero-actions" style="margin-top:1.6rem">
        <a href="{{ route('fondateur.show') }}" class="btn on-dark ghost">{{ __('site.founder.read_more_button') }}</a>
      </div>
    </div>
  </div>
</section>

<section class="section final" id="rejoindre">
  <div class="wrap">
    <p class="eyebrow center">{{ $ctaFinal['eyebrow'] ?? 'Rejoindre le royaume' }}</p>
    <h2>{{ $ctaFinal['title'] ?? '' }}</h2>
    <p class="lead">{{ $ctaFinal['lead'] ?? '' }}</p>
    <div class="final-actions">
      <a href="{{ route('register') }}" class="btn gold">{{ $ctaFinal['button1'] ?? 'Créer mon compte gratuit' }}</a>
      <a href="{{ route('contact') }}" class="btn ghost">{{ $ctaFinal['button2'] ?? 'Nous contacter' }}</a>
    </div>
  </div>
</section>

</main>

</x-public-layout>

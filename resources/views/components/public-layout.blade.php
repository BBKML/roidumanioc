@props(['title' => null, 'description' => null])

@php
    $c = \App\Models\SiteContent::payload();
    $seo = $c['seo'] ?? [];
    $entete = $c['entete'] ?? [];
    $pied = $c['pied'] ?? [];
    // Le nom de ville du pied de page était un lien mort (href="#") — devient un vrai lien
    // vers la carte quand les coordonnées CMS existent (même contrôle que /contact),
    // sinon un simple texte non cliquable (audit UX, Phase 2).
    $footerLat = is_numeric($pied['map_lat'] ?? null) ? (float) $pied['map_lat'] : null;
    $footerLng = is_numeric($pied['map_lng'] ?? null) ? (float) $pied['map_lng'] : null;
    $footerMapUrl = ($footerLat !== null && $footerLng !== null)
        ? "https://www.google.com/maps?q={$footerLat},{$footerLng}"
        : null;

    $baseTitle = $seo['title'] ?? 'Le Roi du Manioc';
    $pageTitle = $title ? $title.' — '.$baseTitle : $baseTitle;
    $metaDesc = $description ?? ($seo['description'] ?? '');

    // Liens associés par position (et non par texte) : le libellé du menu est traduisible
    // (entete.menu.en) sans casser le lien, tant que l'ordre des 5 entrées reste inchangé.
    // Chaque entrée a sa propre page publique (plus de simples ancres sur l'accueil).
    $anchorRoutes = [
        ['url' => route('placali.show'), 'active' => request()->routeIs('placali.show')],
        ['url' => route('formations.index'), 'active' => request()->routeIs('formations.*')],
        ['url' => route('marketplace.index'), 'active' => request()->routeIs('marketplace.index')],
        ['url' => route('communaute.show'), 'active' => request()->routeIs('communaute.show')],
        ['url' => route('fondateur.show'), 'active' => request()->routeIs('fondateur.show')],
    ];
    $menu = $entete['menu'] ?? ['Placali du Roi', 'Formations', 'Marketplace', 'Communauté', 'Le fondateur'];

    // Accueil et Contact encadrent le menu : ce sont des repères structurels de l'interface
    // (comme "Connexion" plus bas), pas du contenu éditorial — traduits via lang/site.php
    // plutôt qu'ajoutés au tableau positionnel `entete.menu` ci-dessus.
    // Placali et Fondateur restent volontairement hors du menu principal (nav allégée sur
    // demande) : les deux pages restent accessibles depuis l'accueil (piliers, sections
    // dédiées) et le pied de page — seuls Formations/Communauté (positions 1, 3 du tableau
    // CMS) y figurent encore, avec leur libellé toujours éditable par l'admin.
    //
    // « Producteurs & Acheteurs » (mise en relation, §14 — le cœur de la V1) est un 6e lien
    // structurel injecté entre Formations et Communauté, hors du tableau positionnel CMS
    // (comme Accueil/Contact) : le libellé vient de lang/site.php, pas de `entete.menu`, et
    // reçoit un traitement visuel distinct (pastille dorée + icône, `.nav-highlight`) plutôt
    // qu'un simple lien texte de plus — cette fonctionnalité n'avait auparavant AUCUNE entrée
    // de navigation (seulement un lien noyé dans le pied de page), ce qui la rendait invisible.
    //
    // Depuis la fusion légère Marketplace ↔ Producteurs & Acheteurs (même bandeau de
    // recherche + sélecteur de mode « Négocier »/« Commander » sur les deux pages), le lien
    // "Marketplace" (position 2 du tableau CMS) n'est plus rendu séparément dans le menu —
    // il serait redondant avec ce même lien, qui amène maintenant sur la même expérience.
    // `$anchorRoutes[2]` reste défini (le sélecteur de mode l'utilise ailleurs) mais n'est
    // plus consommé ici. `$connectLink.active` réagit aussi à `/marketplace` pour que la nav
    // reste bien surlignée quand on y arrive.
    $connectLink = [
        'url' => route('producers.index'),
        'label' => __('site.nav.connect'),
        'active' => request()->routeIs('producers.*') || request()->routeIs('needs.*') || request()->routeIs('marketplace.index'),
        'highlight' => true,
    ];
    $navLinks = [
        ['url' => route('home'), 'label' => __('site.nav.home'), 'active' => request()->routeIs('home')],
        ...array_map(fn ($i) => [
            'url' => $anchorRoutes[$i]['url'],
            'label' => $menu[$i] ?? $anchorRoutes[$i]['url'],
            'active' => $anchorRoutes[$i]['active'],
        ], [1]),
        $connectLink,
        ...array_map(fn ($i) => [
            'url' => $anchorRoutes[$i]['url'],
            'label' => $menu[$i] ?? $anchorRoutes[$i]['url'],
            'active' => $anchorRoutes[$i]['active'],
        ], [3]),
        ['url' => route('contact'), 'label' => __('site.nav.contact'), 'active' => request()->routeIs('contact')],
    ];

    $waRaw = trim($pied['whatsapp'] ?? '');
    $waLink = $waRaw === ''
        ? null
        : (str_starts_with($waRaw, 'http') ? $waRaw : 'https://wa.me/'.preg_replace('/\D+/', '', $waRaw));
@endphp
<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $pageTitle }}</title>
<meta name="description" content="{{ $metaDesc }}">
<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ $baseTitle }}">
<meta property="og:title" content="{{ $pageTitle }}">
<meta property="og:description" content="{{ $metaDesc }}">
<meta property="og:image" content="{{ asset('img/champ-manioc.jpg') }}">
<meta property="og:url" content="{{ url()->current() }}">
<meta name="twitter:card" content="summary_large_image">
<link rel="icon" href="{{ asset('img/logo.png') }}">
<link rel="canonical" href="{{ url()->current() }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,500;0,9..144,600;1,9..144,400;1,9..144,500&family=Manrope:wght@400;500;600;700;800&display=swap">
@vite(['resources/css/vitrine.css', 'resources/js/vitrine.js'])
@livewireStyles
</head>
<body>
<a href="#main" class="skip">{{ __('site.skip_to_content') }}</a>

<header class="site-header" id="header">
  <div class="header-inner">
    <a href="{{ route('home') }}#top" class="brand" aria-label="{{ $entete['brand'] ?? 'Le Roi du Manioc' }} — accueil">
      <img src="{{ asset('img/logo.png') }}" alt="">
      <span class="brand-txt"><b>{{ $entete['brand'] ?? 'Le Roi du Manioc' }}</b><span class="brand-tag">{{ $entete['tagline'] ?? '' }}</span></span>
    </a>
    <nav class="nav" aria-label="Navigation principale">
      @foreach ($navLinks as $link)
        @if (! empty($link['highlight']))
          <a href="{{ $link['url'] }}" class="nav-highlight" @if ($link['active']) aria-current="page" @endif>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="7" cy="12" r="3"/><circle cx="17" cy="12" r="3"/><path d="M10 12h4"/></svg>
            {{ $link['label'] }}
          </a>
        @else
          <a href="{{ $link['url'] }}" @if ($link['active']) aria-current="page" @endif>{{ $link['label'] }}</a>
        @endif
      @endforeach
      <a href="{{ route('login') }}" class="nav-login" aria-label="{{ __('site.nav.login') }}" title="{{ __('site.nav.login') }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/></svg>
      </a>
    </nav>
    <div class="header-cta">
      <div class="lang-switch" role="group" aria-label="Langue / Language">
        <a href="{{ route('locale.switch', 'fr') }}" class="{{ app()->getLocale() === 'fr' ? 'active' : '' }}" hreflang="fr" aria-label="Français" title="Français"><x-flag-icon code="fr" /></a>
        <a href="{{ route('locale.switch', 'en') }}" class="{{ app()->getLocale() === 'en' ? 'active' : '' }}" hreflang="en" aria-label="English" title="English"><x-flag-icon code="en" /></a>
      </div>
      <a href="{{ route('register') }}" class="btn gold sm">{{ $entete['cta'] ?? 'Rejoindre le royaume' }}</a>
      <button class="nav-toggle" id="navToggle" aria-label="{{ __('site.nav.open_menu') }}" aria-expanded="false">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
      </button>
    </div>
  </div>
</header>

<nav class="mobile-menu" id="mobileMenu" aria-label="Menu mobile">
  @foreach ($navLinks as $link)
    <a href="{{ $link['url'] }}" @if (! empty($link['highlight'])) class="is-highlight" @endif @if ($link['active']) aria-current="page" @endif>{{ $link['label'] }}</a>
  @endforeach
  <a href="{{ route('login') }}" class="nav-login">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/></svg>
    {{ __('site.nav.login') }}
  </a>
  <a href="{{ route('register') }}" class="btn gold">{{ $entete['cta'] ?? 'Rejoindre le royaume' }}</a>
  <div class="lang-switch" role="group" aria-label="Langue / Language">
    <a href="{{ route('locale.switch', 'fr') }}" class="{{ app()->getLocale() === 'fr' ? 'active' : '' }}" hreflang="fr" aria-label="Français" title="Français"><x-flag-icon code="fr" /></a>
    <a href="{{ route('locale.switch', 'en') }}" class="{{ app()->getLocale() === 'en' ? 'active' : '' }}" hreflang="en" aria-label="English" title="English"><x-flag-icon code="en" /></a>
  </div>
</nav>

{{ $slot }}

<footer class="site-footer">
  <div class="wrap">
    <div class="footer-top">
      <div class="footer-brand">
        <div class="brand">
          <img src="{{ asset('img/logo.png') }}" alt="" style="width:44px;height:44px;border-radius:50%;background:var(--cream);">
          <span class="brand-txt"><b>{{ $entete['brand'] ?? 'Le Roi du Manioc' }}</b><span class="brand-tag">{{ $entete['tagline'] ?? '' }}</span></span>
        </div>
        <p>{{ $pied['description'] ?? '' }}</p>
        <div class="socials">
          @if (! empty($pied['facebook']))
            <a class="ic-facebook" href="{{ $pied['facebook'] }}" target="_blank" rel="noopener" aria-label="Facebook"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M13 22v-8h3l1-4h-4V7.5c0-1 .3-1.7 1.8-1.7H17V2.2C16.5 2.1 15.4 2 14.2 2 11.5 2 9.7 3.7 9.7 6.7V10H7v4h2.7v8H13z"/></svg></a>
          @endif
          @if (! empty($pied['instagram']))
            <a class="ic-instagram" href="{{ $pied['instagram'] }}" target="_blank" rel="noopener" aria-label="Instagram"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/></svg></a>
          @endif
          @if (! empty($pied['tiktok']))
            <a class="ic-tiktok" href="{{ $pied['tiktok'] }}" target="_blank" rel="noopener" aria-label="TikTok"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M16 3c.3 2.3 1.7 3.9 4 4v3c-1.5.1-2.9-.3-4-1v6.5A6.5 6.5 0 1 1 9.5 9c.4 0 .7 0 1 .1v3.1a3.4 3.4 0 1 0 2.4 3.3V3H16z"/></svg></a>
          @endif
          @if (! empty($pied['youtube']))
            <a class="ic-youtube" href="{{ $pied['youtube'] }}" target="_blank" rel="noopener" aria-label="YouTube"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M23 12s0-3.2-.4-4.7c-.2-.9-.9-1.5-1.8-1.8C19.3 5 12 5 12 5s-7.3 0-8.8.5c-.9.3-1.6.9-1.8 1.8C1 8.8 1 12 1 12s0 3.2.4 4.7c.2.9.9 1.5 1.8 1.8C4.7 19 12 19 12 19s7.3 0 8.8-.5c.9-.3 1.6-.9 1.8-1.8.4-1.5.4-4.7.4-4.7zM9.8 15.3V8.7l5.7 3.3-5.7 3.3z"/></svg></a>
          @endif
          @if ($waLink)
            <a class="ic-whatsapp" href="{{ $waLink }}" target="_blank" rel="noopener" aria-label="WhatsApp"><svg viewBox="0 0 16 16" fill="currentColor"><path d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326zM7.994 14.521a6.6 6.6 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.56 6.56 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592m3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.73.73 0 0 0-.529.247c-.182.198-.691.677-.691 1.654s.71 1.916.81 2.049c.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232"/></svg></a>
          @endif
          @if (! empty($pied['linkedin']))
            <a class="ic-linkedin" href="{{ $pied['linkedin'] }}" target="_blank" rel="noopener" aria-label="LinkedIn"><svg viewBox="0 0 16 16" fill="currentColor"><path d="M0 1.146C0 .513.526 0 1.175 0h13.65C15.474 0 16 .513 16 1.146v13.708c0 .633-.526 1.146-1.175 1.146H1.175C.526 16 0 15.487 0 14.854zm4.943 12.248V6.169H2.542v7.225zm-1.2-8.212c.837 0 1.358-.554 1.358-1.248-.015-.709-.52-1.248-1.342-1.248S2.4 3.226 2.4 3.934c0 .694.521 1.248 1.327 1.248zm4.908 8.212V9.359c0-.216.016-.432.08-.586.173-.431.568-.878 1.232-.878.869 0 1.216.662 1.216 1.634v3.865h2.401V9.25c0-2.22-1.184-3.252-2.764-3.252-1.274 0-1.845.7-2.165 1.193v.025h-.016l.016-.025V6.169h-2.4c.03.678 0 7.225 0 7.225z"/></svg></a>
          @endif
        </div>
      </div>
      <div class="footer-col">
        <h4>{{ __('site.footer.explore') }}</h4>
        <a href="{{ route('placali.show') }}">Placali du Roi</a>
        <a href="{{ route('formations.index') }}">Formations</a>
        <a href="{{ route('marketplace.index') }}">Marketplace</a>
        <a href="{{ route('events.index') }}">Événements</a>
        <a href="{{ route('producers.index') }}">Producteurs</a>
        <a href="{{ route('needs.index') }}">Besoins des acheteurs</a>
        <a href="{{ route('communaute.show') }}">Communauté</a>
      </div>
      <div class="footer-col">
        <h4>Contact</h4>
        <a href="{{ route('contact') }}">{{ __('site.footer.write_to_us') }}</a>
        <a href="mailto:{{ $pied['email'] ?? 'contact@roidumanioc.ci' }}">{{ $pied['email'] ?? 'contact@roidumanioc.ci' }}</a>
        @if ($footerMapUrl)
          <a href="{{ $footerMapUrl }}" target="_blank" rel="noopener">{{ $pied['city'] ?? '' }}</a>
        @else
          <span>{{ $pied['city'] ?? '' }}</span>
        @endif
        <div class="newsletter">
          <form method="POST" action="{{ route('newsletter.store') }}">
            @csrf
            <input type="email" name="email" placeholder="{{ __('site.footer.email_placeholder') }}" aria-label="{{ __('site.footer.email_placeholder') }}" required>
            <button type="submit">{{ __('site.footer.subscribe_button') }}</button>
          </form>
          @if (session('newsletter'))
            <div class="alert-ok on-dark" role="status">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
              <span>{{ session('newsletter') }}</span>
            </div>
          @endif
        </div>
      </div>
    </div>
    <div class="footer-bottom">
      <span>{{ $pied['copyright'] ?? '' }}</span>
      <span><a href="{{ route('login') }}">{{ __('site.footer.member_area') }}</a> · <a href="{{ route('legal.notice') }}">{{ __('site.footer.legal_notice') }}</a> · <a href="{{ route('legal.privacy') }}">{{ __('site.footer.privacy') }}</a> · <a href="{{ route('legal.charter') }}">{{ __('site.footer.charter') }}</a></span>
    </div>
  </div>
</footer>

{{-- Boutons flottants : WhatsApp toujours visible (même numéro que le pied de page),
     retour en haut affiché seulement après un peu de scroll (géré par vitrine.js). --}}
<div class="floating-actions">
  @if ($waLink)
    <a href="{{ $waLink }}" target="_blank" rel="noopener" class="fab fab-whatsapp" aria-label="Contacter sur WhatsApp">
      <svg viewBox="0 0 16 16" fill="currentColor"><path d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326zM7.994 14.521a6.6 6.6 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.56 6.56 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592m3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.73.73 0 0 0-.529.247c-.182.198-.691.677-.691 1.654s.71 1.916.81 2.049c.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232"/></svg>
    </a>
  @endif
  <button type="button" class="fab fab-top" id="scrollTopBtn" aria-label="Retour en haut de page">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
  </button>
</div>

@livewireScripts
</body>
</html>

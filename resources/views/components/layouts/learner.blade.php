@php
    use App\Models\Event;

    $current = request()->route()?->getName();
    $user = auth()->user();

    $isProducer = $user?->isProducer() ?? false;
    $isBuyer = $user?->isBuyer() ?? false;
    // Un admin qui ouvre un écran partagé (ex. superviser une collaboration depuis
    // /admin/collaborations) n'est pas un client : les accroches "Devenir producteur/
    // acheteur" n'ont aucun sens pour lui et laissaient croire qu'il naviguait comme
    // n'importe quel apprenant (confusion signalée en usage réel) — masquées ci-dessous.
    $isAdmin = $user?->isAdmin() ?? false;

    $nav = [
        'Ma formation' => [
            ['learner.dashboard', 'Tableau de bord', 'home'],
            ['learner.catalog', 'Catalogue', 'book'],
            ['learner.progress', 'Ma progression', 'award'],
        ],
        'Le royaume' => [
            ['learner.marketplace', 'Marketplace', 'spark'],
            ['learner.orders', 'Mes commandes', 'cart'],
            ['learner.community', 'Communauté', 'chat'],
        ],
        'Mon royaume' => [
            ...($isProducer ? [
                // Pas de "Tableau de bord" ici : Phase 15, ses informations ont été
                // fusionnées dans le tableau de bord UNIQUE de "Mon espace" (§9/§11 réunis,
                // cf. App\Livewire\Learner\Dashboard) — précisément pour qu'un compte
                // cumulant producteur+acheteur n'ait plus jamais deux liens "Tableau de
                // bord" distincts dans ce même menu (confusion signalée en usage réel).
                ['learner.producer', 'Profil producteur', 'leaf', true],
                ['learner.producer.offers', 'Mes offres', 'box', true],
                // Note terminologie (audit UX, Phase 2) : "Demandes reçues" aurait été
                // trompeur — cette boîte mélange déjà les deux sens (une demande reçue sur
                // une offre ET une demande envoyée à un besoin, cf. docblock de
                // App\Livewire\Producer\Requests). Qualifiée par rôle plutôt que par sens.
                ['learner.producer.requests', 'Mes demandes (producteur)', 'chat', true],
                // Les commandes structurées reçues (§3) vivent désormais dans « Mes
                // commandes » (Le royaume, App\Livewire\Learner\Orders) — un seul onglet
                // « commande » par compte, pas un lien séparé ici en plus.
            ] : ($isAdmin ? [] : [
                ['learner.producer', 'Devenir producteur', 'leaf', false],
            ])),
            ...($isBuyer ? [
                ['learner.buyer', 'Profil acheteur', 'basket', true],
                ['learner.buyer.needs', 'Mes besoins', 'target', true],
                ['learner.buyer.requests', 'Mes demandes (acheteur)', 'chat', true],
                ['learner.buyer.favorites', 'Mes favoris', 'heart', true],
            ] : ($isAdmin ? [] : [
                ['learner.buyer', 'Devenir acheteur', 'basket', false],
            ])),
        ],
        'Compte' => [
            ['account.edit', 'Mon compte', 'users'],
        ],
    ];

    // Groupe "Mon royaume" vide pour un admin sans profil producteur/acheteur : ne pas
    // afficher un intitulé de section flottant sans le moindre lien dessous.
    $nav = array_filter($nav, fn ($items) => $items !== []);

    $titles = [
        'learner.dashboard' => ['Espace apprenant', 'Tableau de bord'],
        'learner.catalog' => ['Espace apprenant', 'Catalogue des formations'],
        'learner.course' => ['Espace apprenant', 'Formation'],
        'learner.progress' => ['Espace apprenant', 'Ma progression'],
        'learner.marketplace' => ['Espace apprenant', 'Marketplace'],
        'learner.orders' => ['Espace apprenant', 'Mes commandes'],
        'learner.community' => ['Espace apprenant', 'Communauté'],
        'learner.checkout' => ['Espace apprenant', 'Finaliser le paiement'],
        'learner.shop-checkout' => ['Espace apprenant', 'Finaliser la commande'],
        'learner.producer' => ['Espace apprenant', 'Profil producteur'],
        'learner.producer.offers' => ['Espace apprenant', 'Mes offres'],
        'learner.producer.offers.create' => ['Espace apprenant', 'Nouvelle offre'],
        'learner.producer.offers.edit' => ['Espace apprenant', "Modifier l'offre"],
        'learner.producer.needs.respond' => ['Espace apprenant', 'Répondre au besoin'],
        'learner.producer.requests' => ['Espace apprenant', 'Mes demandes (producteur)'],
        'learner.buyer' => ['Espace apprenant', 'Profil acheteur'],
        'learner.buyer.needs' => ['Espace apprenant', 'Mes besoins'],
        'learner.buyer.needs.create' => ['Espace apprenant', 'Nouveau besoin'],
        'learner.buyer.needs.edit' => ['Espace apprenant', 'Modifier le besoin'],
        'learner.buyer.offers.contact' => ['Espace apprenant', 'Contacter le producteur'],
        'learner.buyer.requests' => ['Espace apprenant', 'Mes demandes (acheteur)'],
        'learner.buyer.favorites' => ['Espace apprenant', 'Mes favoris'],
        'learner.requests.show' => ['Espace apprenant', 'Demande de mise en relation'],
        'learner.buyer.crop-orders.create' => ['Espace apprenant', 'Passer une commande'],
        'learner.crop-orders.show' => ['Espace apprenant', 'Commande'],
        'account.edit' => ['Compte', 'Mon compte'],
    ];
    [$crumb, $pageTitle] = $titles[$current] ?? ['Espace apprenant', 'Espace apprenant'];

    $icons = [
        'home' => '<path d="M4 11l8-7 8 7M6 10v10h12V10"/>',
        'book' => '<path d="M4 4v16l7-2 7 2V4l-7 2-7-2z"/><path d="M11 6v14"/>',
        'award' => '<circle cx="12" cy="9" r="6"/><path d="M9 14l-2 7 5-3 5 3-2-7"/>',
        'spark' => '<path d="M12 3v6M12 15v6M3 12h6M15 12h6"/>',
        'cart' => '<circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/><path d="M2 3h3l2.5 13h11L21 7H6"/>',
        'chat' => '<path d="M4 5h16v11H9l-4 4V5z"/>',
        'users' => '<circle cx="9" cy="8" r="3.2"/><path d="M3.5 20c.7-3.4 3-5 5.5-5s4.8 1.6 5.5 5"/><path d="M16 5.5a3 3 0 0 1 0 5.6M17.5 20c-.3-2-1-3.5-2-4.5"/>',
        'leaf' => '<path d="M4 20c8 0 16-8 16-16-8 0-16 8-16 16z"/><path d="M4 20c2-6 6-10 12-12"/>',
        'basket' => '<path d="M4 9h16l-1.5 10a2 2 0 0 1-2 1.8H7.5a2 2 0 0 1-2-1.8L4 9z"/><path d="M8 9V7a4 4 0 0 1 8 0v2M9 13v3M15 13v3"/>',
        'box' => '<path d="M21 8l-9-5-9 5 9 5 9-5z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/>',
        'target' => '<circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="4"/><circle cx="12" cy="12" r=".5" fill="currentColor"/>',
        'heart' => '<path d="M12 21s-7-4.35-9.5-8.5C1 9 2.5 5.5 6 5c2-.3 3.7.8 6 3 2.3-2.2 4-3.3 6-3 3.5.5 5 4 3.5 7.5C19 16.65 12 21 12 21z"/>',
    ];
    $svg = fn ($k) => '<svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'.($icons[$k] ?? '').'</svg>';
@endphp
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $pageTitle }} — Espace apprenant · Le Roi du Manioc</title>
<link rel="icon" href="{{ asset('img/logo.png') }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;1,9..144,500&family=Manrope:wght@400;500;600;700;800&display=swap">
@vite(['resources/css/admin.css', 'resources/js/admin.js'])
@livewireStyles
</head>
<body>
<div class="layout">
  <aside class="sidebar" id="sidebar">
    <a class="brand" href="{{ route('home') }}" title="Retour au site public">
      <img src="{{ asset('img/logo.png') }}" alt="">
      <span class="brand-txt"><b>Le Roi du Manioc</b><span class="brand-tag">{{ $isAdmin ? 'Vue administrateur' : 'Espace apprenant' }}</span></span>
    </a>

    <div class="side-user">
      <span class="avatar">{{ $user?->initials() }}</span>
      <span class="su-txt">
        <b>{{ $user?->name }}</b>
        <span class="su-role">{{ $isAdmin ? 'Administrateur' : 'Apprenant' }}</span>
      </span>
    </div>

    <nav id="nav">
      @foreach ($nav as $group => $items)
        <div class="nav-label">{{ $group }}</div>
        @foreach ($items as $item)
          @php [$route, $label, $icon, $active] = [$item[0], $item[1], $item[2], $item[3] ?? true]; @endphp
          <a class="nav-item {{ $current === $route ? 'on' : '' }}" href="{{ route($route) }}" wire:navigate>
            {!! $svg($icon) !!}<span>{{ $label }}</span>
            @unless ($active)<span class="tag">Activer</span>@endunless
          </a>
        @endforeach
      @endforeach
    </nav>

    <div class="sidebar-foot">
      @if ($isAdmin)
        <a href="{{ route('admin.dashboard') }}" class="foot-site" style="margin-bottom:.4rem" wire:navigate>← Retour à l'administration</a>
      @endif
      <a href="{{ route('home') }}" class="foot-site">← Voir le site public</a>
      <form method="POST" action="{{ route('logout') }}" style="margin-top:.7rem">
        @csrf
        <button type="submit">Se déconnecter</button>
      </form>
    </div>
  </aside>

  <div class="main">
    <header class="topbar">
      <button class="burger" id="burger" aria-label="Menu">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
      </button>
      <div>
        <div class="crumb">{{ $crumb }}</div>
        <h1>{{ $pageTitle }}</h1>
      </div>
      <div class="sp"></div>
      <livewire:learner.notification-bell />
      <span class="chip"><span class="avatar">{{ $user?->initials() }}</span><span class="muted">{{ $user?->name }}</span></span>
    </header>

    <div class="view">
      {{ $slot }}
    </div>
  </div>
</div>

<div class="drawer-back" id="drawerBack"></div>

<x-adm.confirm-dialog />

@if (session('flash'))
  <div class="lw-flash" role="status" aria-live="polite">{{ session('flash') }}</div>
@endif

@livewireScripts
</body>
</html>

@php
    $current = request()->route()?->getName();
    $user = auth()->user();

    $titles = [
        'admin.dashboard' => ['Administration', "Vue d'ensemble"],
        'admin.payments' => ['Administration', 'Paiements à vérifier'],
        'admin.orders' => ['Administration', 'Commandes'],
        'admin.messages' => ['Administration', 'Messages reçus'],
        'admin.content' => ['Contenu', 'Contenu du site public'],
        'admin.formations' => ['Contenu', 'Formations'],
        'admin.lessons' => ['Contenu', 'Leçons'],
        'admin.marketplace' => ['Contenu', 'Marketplace'],
        'admin.shop' => ['Contenu', 'Boutique officielle'],
        'admin.events' => ['Contenu', 'Événements'],
        'admin.community' => ['Contenu', 'Communauté'],
        'admin.settings' => ['Gestion', 'Paramètres'],
        'admin.members' => ['Gestion', 'Membres & comptes'],
        'admin.activity' => ['Gestion', "Journal d'activité"],
        'admin.newsletter' => ['Gestion', 'Infolettre'],
        'admin.registration-forms' => ['Campagnes', "Formulaires d'inscription"],
        'admin.registration-forms.create' => ['Campagnes', 'Nouveau formulaire'],
        'admin.registration-forms.edit' => ['Campagnes', 'Modifier le formulaire'],
        'admin.registration-leads' => ['Campagnes', 'Prospects'],
        // Mise en relation (§25/§37) — absents du dictionnaire jusqu'ici : l'onglet du
        // navigateur et le fil d'ariane retombaient sur "Administration / Administration"
        // au lieu du vrai nom de l'écran (audit UX, Phase 2).
        'admin.producers' => ['Mise en relation', 'Producteurs'],
        'admin.producers.show' => ['Mise en relation', 'Fiche producteur'],
        'admin.buyers' => ['Mise en relation', 'Acheteurs'],
        'admin.crop-offers' => ['Mise en relation', 'Offres'],
        'admin.buyer-needs' => ['Mise en relation', 'Besoins'],
        'admin.connection-requests' => ['Mise en relation', 'Demandes'],
        'admin.collaborations' => ['Mise en relation', 'Collaborations'],
        'admin.reviews' => ['Mise en relation', 'Avis'],
        'admin.conversations' => ['Pilotage', 'Conversations signalées'],
        'account.edit' => ['Compte', 'Mon compte'],
    ];
    [$crumb, $pageTitle] = $titles[$current] ?? ['Administration', 'Administration'];
@endphp
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $pageTitle }} — Administration · Le Roi du Manioc</title>
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
      <span class="brand-txt"><b>Le Roi du Manioc</b><span class="brand-tag">Tableau de bord</span></span>
    </a>

    <div class="side-user">
      <span class="avatar">{{ $user?->initials() }}</span>
      <span class="su-txt">
        <b>{{ $user?->name }}</b>
        <span class="su-role">Administrateur</span>
      </span>
    </div>

    <livewire:admin.nav />


    <div class="sidebar-foot">
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

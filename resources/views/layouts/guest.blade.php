@props([
    'brandTitle' => __('guest.login.brand_title'),
])
<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $title ?? __('guest.login.title') }} — Le Roi du Manioc</title>
<link rel="icon" href="{{ asset('img/logo.png') }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600&family=Manrope:wght@400;500;600;700;800&display=swap">
@vite(['resources/css/auth.css', 'resources/js/auth.js'])
</head>
<body>
<div class="auth">
  <aside class="auth-brand">
    <div class="auth-brand-top">
      <a class="b-logo" href="{{ route('home') }}">
        <img src="{{ asset('img/logo.png') }}" alt="">
        <span><b>Le Roi du Manioc</b><span class="tag">L'or des visionnaires</span></span>
      </a>
      <div class="lang-switch" role="group" aria-label="{{ __('guest.lang_switch_label') }}">
        <a href="{{ route('locale.switch', 'fr') }}" class="{{ app()->getLocale() === 'fr' ? 'active' : '' }}" hreflang="fr" aria-label="Français" title="Français"><x-flag-icon code="fr" /></a>
        <a href="{{ route('locale.switch', 'en') }}" class="{{ app()->getLocale() === 'en' ? 'active' : '' }}" hreflang="en" aria-label="English" title="English"><x-flag-icon code="en" /></a>
      </div>
    </div>
    <h2>{{ $brandTitle }}</h2>
    <ul>
      <li>{{ __('guest.sidebar.bullet1') }}</li>
      <li>{{ __('guest.sidebar.bullet2') }}</li>
      <li>{{ __('guest.sidebar.bullet3') }}</li>
    </ul>
    <a class="back" href="{{ route('home') }}">{{ __('guest.back_to_site') }}</a>
  </aside>

  <main class="auth-scroll">
    <div class="auth-form">
      {{ $slot }}
    </div>
  </main>
</div>
</body>
</html>

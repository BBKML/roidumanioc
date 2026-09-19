<x-public-layout title="Accès refusé">

<main id="main">
<span id="top"></span>

<section class="error-page">
  <div class="wrap">
    <p class="eyebrow center">Erreur 403</p>
    <div class="code" aria-hidden="true">403</div>
    <h1>Vous n'avez pas l'autorisation d'accéder à cette page.</h1>
    <p>Si vous pensez qu'il s'agit d'une erreur, contactez l'administrateur. En attendant, revenez vers un espace accessible.</p>
    <div class="final-actions">
      <a href="{{ auth()->check() ? route('dashboard') : route('home') }}" class="btn gold">{{ auth()->check() ? 'Retour à mon espace' : "Retour à l'accueil" }}</a>
      <a href="{{ route('contact') }}" class="btn ghost">Nous signaler le problème</a>
    </div>
  </div>
</section>

</main>

</x-public-layout>

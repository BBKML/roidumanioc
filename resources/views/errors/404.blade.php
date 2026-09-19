<x-public-layout title="Page introuvable">

<main id="main">
<span id="top"></span>

<section class="error-page">
  <div class="wrap">
    <p class="eyebrow center">Erreur 404</p>
    <div class="code" aria-hidden="true">404</div>
    <h1>Cette page s'est égarée en chemin.</h1>
    <p>Le lien est peut-être ancien, ou l'adresse mal orthographiée. Reprenons depuis l'accueil.</p>
    <div class="final-actions">
      <a href="{{ route('home') }}" class="btn gold">Retour à l'accueil</a>
      <a href="{{ route('contact') }}" class="btn ghost">Nous signaler le problème</a>
    </div>
  </div>
</section>

</main>

</x-public-layout>

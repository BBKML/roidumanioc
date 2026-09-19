<x-public-layout :title="$title">

<main id="main">
<span id="top"></span>

<section class="section">
  <div class="section-head">
    <h1>{{ $title }}</h1>
  </div>
  <div class="wrap">
    @if ($html)
      <div class="legal-content">{!! $html !!}</div>
    @else
      <p class="lead">Cette page sera complétée prochainement par l'équipe du Roi du Manioc.</p>
    @endif
  </div>
</section>

</main>

</x-public-layout>

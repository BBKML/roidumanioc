{{-- Placeholder — le portage complet de la vitrine (index.html) se fait en Phase 2. --}}
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ data_get($content, 'seo.title', 'Le Roi du Manioc') }}</title>
<meta name="description" content="{{ data_get($content, 'seo.description') }}">
<style>
  body{font-family:system-ui,sans-serif;background:#16271c;color:#f6f2e6;margin:0;padding:3rem 1.5rem;line-height:1.6}
  .box{max-width:720px;margin:0 auto}
  h1{font-size:2rem;margin:.2rem 0}
  .gold{color:#c6952f;font-weight:800;letter-spacing:.15em;text-transform:uppercase;font-size:.75rem}
  ul{padding-left:1.1rem} a{color:#c6952f}
  .card{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.12);border-radius:12px;padding:1rem 1.2rem;margin:.6rem 0}
</style>
</head>
<body>
<div class="box">
  <p class="gold">{{ data_get($content, 'entete.tagline') }}</p>
  <h1>{{ data_get($content, 'hero.title') }}</h1>
  <p>{{ data_get($content, 'hero.text') }}</p>

  <p><a href="{{ route('login') }}">Connexion</a> &nbsp;·&nbsp; <a href="{{ route('register') }}">Créer un compte</a></p>

  <div class="card">
    <strong>Données servies depuis la base ✅</strong>
    <ul>
      <li>{{ $formations->count() }} formation(s) publiée(s)</li>
      <li>{{ $listings->count() }} annonce(s) marketplace</li>
      <li>{{ $products->count() }} produit(s) en boutique</li>
      <li>{{ $events->count() }} événement(s) à venir</li>
      <li>{{ $testimonials->count() }} témoignage(s) · {{ $awards->count() }} distinction(s)</li>
    </ul>
  </div>

  <h2 style="font-size:1.1rem">Formations</h2>
  @foreach ($formations as $f)
    <div class="card">
      <strong>{{ $f->title }}</strong> — {{ $f->isFree() ? 'Gratuit' : number_format($f->price, 0, ',', ' ').' FCFA' }}
      <br><small>{{ $f->category }} · {{ $f->lessons_count }} leçons</small>
    </div>
  @endforeach

  <p style="margin-top:2rem;opacity:.6;font-size:.85rem">
    Phase 1 — fondation posée. Le rendu final de cette page reprend <code>index.html</code> (Phase 2).
  </p>
</div>
</body>
</html>

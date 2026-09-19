<div style="display:flex;flex-direction:column;gap:1.4rem">

  <div class="page-intro">
    <div>
      <h2>Catalogue des formations</h2>
      <p>Les formations gratuites sont accessibles immédiatement. Pour les formations premium, réglez en ligne (Wave, Orange Money, MTN, Moov, virement ou carte) — l'accès s'active après vérification du paiement.</p>
    </div>
  </div>

  <div class="grid g-3">
    @forelse ($formations as $f)
      <div class="card" style="padding:0;overflow:hidden" wire:key="cat-{{ $f->id }}">
        @if ($f->image_path)
          <img src="{{ asset($f->image_path) }}" alt="" style="width:100%;height:130px;object-fit:cover;object-position:center 20%">
        @endif
        <div style="padding:1rem">
          <div style="display:flex;gap:.4rem;margin-bottom:.5rem;flex-wrap:wrap">
            @if ($f->isFree())
              <span class="badge-free">Gratuit</span>
            @else
              <span class="badge-prem">{{ number_format($f->price, 0, ',', ' ') }} FCFA</span>
            @endif
            <span class="pill neutral"><span class="dot"></span>{{ $f->category }}</span>
          </div>
          <h3 style="font-size:1.02rem">{{ $f->title }}</h3>
          <p class="muted" style="font-size:.8rem;margin:.35rem 0 .9rem">{{ $f->description }}</p>
          <p class="muted" style="font-size:.75rem;margin-bottom:.8rem">{{ $f->lessons_count }} leçon{{ $f->lessons_count > 1 ? 's' : '' }}</p>

          @if ($f->is_enrolled)
            <a class="btn" style="width:100%;justify-content:center" href="{{ route('learner.course', $f) }}" wire:navigate>Accéder</a>
          @elseif ($f->is_pending)
            <button class="btn ghost" style="width:100%;justify-content:center" disabled>Paiement en vérification…</button>
          @elseif ($f->isFree())
            <button class="btn" style="width:100%;justify-content:center" wire:click="enrollFree({{ $f->id }})">Commencer gratuitement</button>
          @else
            <button class="btn gold" style="width:100%;justify-content:center" wire:click="buy({{ $f->id }})">S'inscrire · {{ number_format($f->price, 0, ',', ' ') }} FCFA</button>
          @endif
        </div>
      </div>
    @empty
      <div class="card"><div class="empty"><p>Aucune formation publiée pour l'instant.</p></div></div>
    @endforelse
  </div>

</div>

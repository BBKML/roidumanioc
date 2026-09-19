<div style="display:flex;flex-direction:column;gap:1.4rem">

  <div class="page-intro">
    <div>
      <h2>Mes favoris</h2>
      <p>Les producteurs que vous avez mis de côté depuis le catalogue public.</p>
    </div>
    <a class="btn ghost" href="{{ route('producers.index') }}" wire:navigate>
      <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="8" r="3.2"/><path d="M3.5 20c.7-3.4 3-5 5.5-5s4.8 1.6 5.5 5"/></svg>
      Voir le catalogue
    </a>
  </div>

  @if ($favorites->isEmpty())
    <div class="card"><div class="empty">
      <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-7-4.35-9.5-8.5C1 9 2.5 5.5 6 5c2-.3 3.7.8 6 3 2.3-2.2 4-3.3 6-3 3.5.5 5 4 3.5 7.5C19 16.65 12 21 12 21z"/></svg>
      <p>Vous n'avez encore ajouté aucun producteur à vos favoris.</p>
      <a class="btn" href="{{ route('producers.index') }}" wire:navigate style="margin-top:.6rem">Parcourir les producteurs</a>
    </div></div>
  @else
    <div class="grid g-3">
      @foreach ($favorites as $producer)
        <div class="card" wire:key="fav-{{ $producer->id }}">
          <div class="card-head">
            <h3>{{ $producer->business_name }}</h3>
            @if ($producer->isVerified())
              <span class="pill ok"><span class="dot"></span>Vérifié</span>
            @endif
          </div>

          <p class="muted" style="font-size:.84rem;margin:0 0 .4rem">{{ $producer->activity_type->label() }} · {{ $producer->zone }}</p>
          <p style="font-size:.86rem;margin:0 0 .8rem">
            @if ($producer->reviews_count > 0)
              {{ number_format((float) $producer->avg_rating, 1, ',', ' ') }}/5 sur {{ $producer->reviews_count }} avis
            @else
              Pas encore d'évaluation
            @endif
          </p>

          @if ($producer->cropOffers->isNotEmpty())
            <ul class="reset" style="display:flex;flex-direction:column;gap:.3rem;margin:0 0 .8rem;font-size:.84rem">
              @foreach ($producer->cropOffers as $offer)
                <li><a class="link-btn" href="{{ route('learner.buyer.offers.contact', $offer) }}" wire:navigate>{{ $offer->product_name }} →</a></li>
              @endforeach
            </ul>
          @endif

          <div style="display:flex;justify-content:flex-end">
            <button class="iact danger" wire:click="unfavorite({{ $producer->id }})" data-confirm="Retirer {{ $producer->business_name }} de vos favoris ?" title="Retirer des favoris">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M9 7V5h6v2M6 7l1 13h10l1-13"/></svg>
            </button>
          </div>
        </div>
      @endforeach
    </div>
  @endif

</div>

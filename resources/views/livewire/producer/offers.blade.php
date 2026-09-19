<div style="display:flex;flex-direction:column;gap:1.4rem">

  <div class="page-intro">
    <div>
      <h2>Mes offres</h2>
      <p>Récoltes, boutures, produits transformés et intrants proposés à la vente.</p>
    </div>
    <a class="btn" href="{{ route('learner.producer.offers.create') }}" wire:navigate>
      <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
      Nouvelle offre
    </a>
  </div>

  @if ($offers->isEmpty())
    <div class="card"><div class="empty">
      <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4v16l7-2 7 2V4l-7 2-7-2z"/></svg>
      <p>Vous n'avez encore publié aucune offre.</p>
      <a class="btn" href="{{ route('learner.producer.offers.create') }}" wire:navigate style="margin-top:.6rem">Créer ma première offre</a>
    </div></div>
  @else
    <div class="grid g-3">
      @foreach ($offers as $offer)
        <div class="card" wire:key="offer-{{ $offer->id }}">
          @if ($offer->photos->isNotEmpty())
            <img src="{{ \Illuminate\Support\Facades\Storage::url($offer->photos->first()->path) }}" alt=""
                 style="width:100%;height:140px;object-fit:cover;border-radius:8px;margin-bottom:.7rem">
          @endif

          <div class="card-head">
            <h3>{{ $offer->product_name }}</h3>
            <x-adm.pill :status="$offer->status" />
          </div>

          <p class="muted" style="font-size:.84rem;margin:0 0 .4rem">
            {{ number_format((float) $offer->quantity, 2, ',', ' ') }} {{ $offer->unit->label() }}
            @if ($offer->variety) · {{ $offer->variety }} @endif
          </p>
          <p style="font-size:.86rem;margin:0 0 .8rem">
            {{ $offer->price_indicative ? number_format($offer->price_indicative, 0, ',', ' ').' FCFA' : 'Prix à convenir' }}
            <span class="muted"> · {{ $offer->location }}</span>
          </p>

          <div style="display:flex;gap:.5rem;align-items:center">
            <button wire:click="toggleAvailability({{ $offer->id }})"
                    class="pill {{ $offer->is_available ? 'ok' : 'warn' }}"
                    style="border:none;cursor:pointer">
              <span class="dot"></span>{{ $offer->is_available ? 'Disponible' : 'Indisponible' }}
            </button>
            <div class="sp"></div>
            <a class="iact" href="{{ route('learner.producer.offers.edit', $offer) }}" wire:navigate title="Modifier">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 20h4L20 8l-4-4L4 16v4z"/></svg>
            </a>
            <button class="iact danger" wire:click="delete({{ $offer->id }})" data-confirm="Supprimer cette offre ?" title="Supprimer">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M9 7V5h6v2M6 7l1 13h10l1-13"/></svg>
            </button>
          </div>
        </div>
      @endforeach
    </div>

    {{ $offers->links() }}
  @endif

</div>

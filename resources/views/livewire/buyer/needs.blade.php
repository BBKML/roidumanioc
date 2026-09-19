<div style="display:flex;flex-direction:column;gap:1.4rem">

  <div class="page-intro">
    <div>
      <h2>Mes besoins</h2>
      <p>Publiez ce que vous recherchez — les producteurs pourront vous répondre.</p>
    </div>
    <a class="btn" href="{{ route('learner.buyer.needs.create') }}" wire:navigate>
      <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
      Nouveau besoin
    </a>
  </div>

  @if ($needs->isEmpty())
    <div class="card"><div class="empty">
      <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4v16l7-2 7 2V4l-7 2-7-2z"/></svg>
      <p>Vous n'avez encore publié aucun besoin.</p>
      <a class="btn" href="{{ route('learner.buyer.needs.create') }}" wire:navigate style="margin-top:.6rem">Publier mon premier besoin</a>
    </div></div>
  @else
    <div class="grid g-3">
      @foreach ($needs as $need)
        <div class="card" wire:key="need-{{ $need->id }}">
          <div class="card-head">
            <h3>{{ $need->product_wanted }}</h3>
            <x-adm.pill :status="$need->status" />
          </div>

          <p class="muted" style="font-size:.84rem;margin:0 0 .4rem">
            {{ number_format((float) $need->quantity, 2, ',', ' ') }} {{ $need->unit->label() }}
            · {{ $need->frequency === 'recurrent' ? 'Récurrent' : 'Ponctuel' }}
          </p>
          <p style="font-size:.86rem;margin:0 0 .8rem">
            {{ $need->budget_indicative ? number_format($need->budget_indicative, 0, ',', ' ').' FCFA' : 'Budget à convenir' }}
            <span class="muted"> · {{ $need->location }}</span>
          </p>
          @if ($need->wanted_date)
            <p class="muted" style="font-size:.8rem;margin:0 0 .8rem">Souhaité pour le {{ $need->wanted_date->translatedFormat('d F Y') }}</p>
          @endif

          <div style="display:flex;gap:.5rem;align-items:center">
            <div class="sp"></div>
            <a class="iact" href="{{ route('learner.buyer.needs.edit', $need) }}" wire:navigate title="Modifier">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 20h4L20 8l-4-4L4 16v4z"/></svg>
            </a>
            <button class="iact danger" wire:click="delete({{ $need->id }})" data-confirm="Supprimer ce besoin ?" title="Supprimer">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M9 7V5h6v2M6 7l1 13h10l1-13"/></svg>
            </button>
          </div>
        </div>
      @endforeach
    </div>

    {{ $needs->links() }}
  @endif

</div>

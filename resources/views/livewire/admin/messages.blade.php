<div style="display:flex;flex-direction:column;gap:1.4rem">

  <div class="page-intro">
    <div>
      <h2>Messages reçus</h2>
      <p>Messages envoyés depuis le formulaire de contact du site. Chaque message est aussi
        envoyé par e-mail — ici vous suivez ce qui a été traité et vous pouvez répondre.</p>
    </div>
  </div>

  <div style="display:flex;gap:.8rem;flex-wrap:wrap;align-items:center">
    <label class="search" style="background:var(--paper)">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
      <input type="search" wire:model.live.debounce.300ms="search" placeholder="Nom, e-mail, sujet, texte…">
    </label>
    <div class="tabs">
      <button class="{{ $filter === 'inbox' ? 'on' : '' }}" wire:click="setFilter('inbox')">À traiter ({{ $counts['inbox'] }})</button>
      <button class="{{ $filter === 'traite' ? 'on' : '' }}" wire:click="setFilter('traite')">Traités ({{ $counts['traite'] }})</button>
      <button class="{{ $filter === 'spam' ? 'on' : '' }}" wire:click="setFilter('spam')">Spam ({{ $counts['spam'] }})</button>
      <button class="{{ $filter === 'tous' ? 'on' : '' }}" wire:click="setFilter('tous')">Tous</button>
    </div>
    <x-adm.loading-note target="search,setFilter" />
  </div>

  <div style="display:flex;flex-direction:column;gap:.7rem">
    @forelse ($messages as $m)
      <div class="card" wire:key="msg-{{ $m->id }}" style="{{ $m->status->value === 'nouveau' ? 'border-color:var(--leaf)' : '' }}">
        <div style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;align-items:flex-start">
          <button class="link-btn" style="text-align:left" wire:click="open({{ $m->id }})">
            <b style="font-size:.95rem">{{ $m->subject ?: '(sans sujet)' }}</b>
            <div class="muted" style="font-weight:500;font-size:.82rem">
              {{ $m->name }} · {{ $m->email }}@if ($m->phone) · {{ $m->phone }}@endif
            </div>
          </button>
          <div style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap">
            <span class="muted" style="font-size:.76rem">{{ $m->created_at->diffForHumans() }}</span>
            @php $cls = ['nouveau' => 'warn', 'lu' => 'info', 'traite' => 'ok', 'spam' => 'danger'][$m->status->value]; @endphp
            <span class="pill {{ $cls }}"><span class="dot"></span>{{ $m->status->label() }}</span>
            @if ($m->replied_at)<span class="pill neutral" title="Répondu le {{ $m->replied_at->format('d/m/Y') }}"><span class="dot"></span>répondu</span>@endif
          </div>
        </div>

        @if ($openId !== $m->id)
          <p class="muted" style="margin:.6rem 0 0;font-size:.85rem">{{ $m->excerpt() }}</p>
        @else
          <div style="margin-top:.9rem;border-top:1px solid var(--sand);padding-top:.9rem">
            <p style="white-space:pre-line;font-size:.9rem;margin:0 0 1rem">{{ $m->message }}</p>

            <div class="row-actions" style="justify-content:flex-start;margin-bottom:1rem">
              <a class="btn sm ghost" href="{{ $m->mailtoLink() }}">Répondre dans ma boîte</a>
              @if ($m->whatsappLink())
                <a class="btn sm ghost" href="{{ $m->whatsappLink() }}" target="_blank" rel="noopener">WhatsApp</a>
              @endif
              @if ($m->status->value !== 'traite')
                <button class="btn sm" wire:click="markHandled({{ $m->id }})">Marquer traité</button>
              @else
                <button class="btn sm ghost" wire:click="reopen({{ $m->id }})">Rouvrir</button>
              @endif
              @if ($m->status->value !== 'spam')
                <button class="btn sm danger" wire:click="markSpam({{ $m->id }})" data-confirm="Classer ce message en spam ?">Spam</button>
              @endif
              <button class="iact danger" wire:click="delete({{ $m->id }})" data-confirm="Supprimer définitivement ce message ?" title="Supprimer">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M9 7V5h6v2M6 7l1 13h10l1-13"/></svg>
              </button>
            </div>

            {{-- Répondre depuis l'application --}}
            <div class="field" style="margin:0">
              <label>Répondre par e-mail (envoyé à {{ $m->email }})</label>
              <textarea wire:model="replyBody" rows="4" placeholder="Bonjour {{ \Illuminate\Support\Str::of($m->name)->explode(' ')->first() }}, …"></textarea>
              @error('replyBody') <span class="inline-err">{{ $message }}</span> @enderror
            </div>
            <button class="btn sm" style="margin-top:.5rem" wire:click="reply({{ $m->id }})" wire:loading.attr="disabled" wire:target="reply">
              Envoyer la réponse
            </button>

            {{-- Note interne --}}
            <div class="field" style="margin:1rem 0 0">
              <label>Note interne (non envoyée)</label>
              <textarea wire:model="note" rows="2" placeholder="Suivi, contexte…"></textarea>
            </div>
            <button class="btn sm ghost" style="margin-top:.4rem" wire:click="saveNote({{ $m->id }})">Enregistrer la note</button>

            @if ($m->handledBy)
              <p class="muted" style="font-size:.76rem;margin:.8rem 0 0">Traité par {{ $m->handledBy->name }} le {{ $m->handled_at?->format('d/m/Y H:i') }}</p>
            @endif
          </div>
        @endif
      </div>
    @empty
      <div class="card"><div class="empty">
        @if ($search !== '')
          <p>Aucun message ne correspond à cette recherche.</p>
          <button type="button" class="link-btn" wire:click="resetFilters">Réinitialiser la recherche</button>
        @else
          @switch($filter)
            @case('inbox') <p>Aucun message à traiter — tout est à jour !</p> @break
            @case('traite') <p>Aucun message traité pour l'instant.</p> @break
            @case('spam') <p>Aucun message marqué comme spam.</p> @break
            @default <p>Aucun message pour le moment.</p>
          @endswitch
        @endif
      </div></div>
    @endforelse
  </div>

  {{ $messages->links() }}

</div>

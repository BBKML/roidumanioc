<div style="display:flex;flex-direction:column;gap:1.4rem">

  <div class="page-intro">
    <div>
      <h2>Communauté</h2>
      <p>Modérez les questions et réponses des membres. Les questions masquées disparaissent de l'espace communauté.</p>
    </div>
  </div>

  <div class="tabs">
    <button class="{{ $filter === 'tous' ? 'on' : '' }}" wire:click="setFilter('tous')">Toutes</button>
    <button class="{{ $filter === 'signale' ? 'on' : '' }}" wire:click="setFilter('signale')">Signalées ({{ $counts['signale'] }})</button>
    <button class="{{ $filter === 'masque' ? 'on' : '' }}" wire:click="setFilter('masque')">Masquées ({{ $counts['masque'] }})</button>
    <button class="{{ $filter === 'visible' ? 'on' : '' }}" wire:click="setFilter('visible')">Visibles ({{ $counts['visible'] }})</button>
  </div>
  <x-adm.loading-note target="setFilter" />

  <div class="grid" style="gap:.8rem">
    @forelse ($posts as $post)
      <div class="card" wire:key="post-{{ $post->id }}">
        <div style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap">
          <div style="min-width:0">
            <b>{{ $post->author_name }}</b>
            <span class="muted">· {{ $post->created_at?->diffForHumans() }} · {{ $post->replies_count }} réponse{{ $post->replies_count > 1 ? 's' : '' }}</span>
            <p style="margin:.5rem 0 0">{{ $post->body }}</p>
            @if ($post->replies_count)
              <button class="link-btn" style="margin-top:.5rem" wire:click="toggleReplies({{ $post->id }})">
                {{ $openPostId === $post->id ? 'Masquer les réponses' : 'Voir les réponses' }} →
              </button>
            @endif
          </div>
          <div style="display:flex;gap:.4rem;align-items:flex-start;flex-wrap:wrap">
            <x-adm.pill :status="$post->status" />
            @if ($post->status->value !== 'masque')
              <button class="btn sm ghost" wire:click="hide({{ $post->id }})">Masquer</button>
            @else
              <button class="btn sm ghost" wire:click="show({{ $post->id }})">Rendre visible</button>
            @endif
            <button class="btn sm danger" wire:click="deletePost({{ $post->id }})" data-confirm="Supprimer cette question et toutes ses réponses ?">
              <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M9 7V5h6v2M6 7l1 13h10l1-13"/></svg>
            </button>
          </div>
        </div>

        @if ($openPostId === $post->id)
          <div style="margin-top:1rem;border-top:1px solid var(--sand);padding-top:.8rem;display:flex;flex-direction:column;gap:.6rem">
            @forelse ($replies as $reply)
              <div wire:key="reply-{{ $reply->id }}" style="display:flex;justify-content:space-between;gap:1rem;font-size:.84rem">
                <div><b>{{ $reply->author_name }}</b> <span class="muted">· {{ $reply->created_at?->diffForHumans() }}</span><br>{{ $reply->body }}</div>
                <button class="iact danger" wire:click="deleteReply({{ $reply->id }})" data-confirm="Supprimer cette réponse ?" title="Supprimer">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M9 7V5h6v2M6 7l1 13h10l1-13"/></svg>
                </button>
              </div>
            @empty
              <p class="muted" style="font-size:.82rem">Aucune réponse enregistrée.</p>
            @endforelse
          </div>
        @endif
      </div>
    @empty
      <div class="card"><div class="empty"><p>Aucune question dans cette vue.</p></div></div>
    @endforelse
  </div>

</div>

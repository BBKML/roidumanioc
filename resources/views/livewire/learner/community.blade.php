<div style="display:flex;flex-direction:column;gap:1.2rem">

  <div class="page-intro">
    <div>
      <h2>Communauté</h2>
      <p>Posez vos questions, partagez vos réussites. Les échanges sont visibles par tous les membres.</p>
    </div>
  </div>

  <div class="card">
    <div class="field" style="margin:0">
      <label>Votre message</label>
      <textarea wire:model="body" rows="3" placeholder="Ex : Quel espacement pour la variété Yavo ?"></textarea>
      @error('body') <span class="inline-err">{{ $message }}</span> @enderror
    </div>
    <div style="text-align:right;margin-top:.7rem">
      <button class="btn" wire:click="publish">Publier</button>
    </div>
  </div>

  <div class="grid" style="gap:.8rem">
    @forelse ($posts as $post)
      <div class="card" wire:key="post-{{ $post->id }}">
        <b>{{ $post->author_name }}</b>
        <span class="muted">· {{ $post->created_at?->diffForHumans() }} · {{ $post->replies->count() }} réponse{{ $post->replies->count() > 1 ? 's' : '' }}</span>
        <p style="margin:.5rem 0 0">{{ $post->body }}</p>

        @foreach ($post->replies as $reply)
          <div wire:key="reply-{{ $reply->id }}" style="margin-top:.7rem;padding-left:1rem;border-left:2px solid var(--sand)">
            <b style="font-size:.86rem">{{ $reply->author_name }}</b>
            <span class="muted" style="font-size:.78rem">· {{ $reply->created_at?->diffForHumans() }}</span>
            <p style="margin:.2rem 0 0;font-size:.88rem">{{ $reply->body }}</p>
          </div>
        @endforeach

        <div style="margin-top:.8rem">
          <button class="link-btn" wire:click="startReply({{ $post->id }})">
            {{ $replyTo === $post->id ? 'Annuler' : 'Répondre' }}
          </button>
        </div>

        @if ($replyTo === $post->id)
          <div style="margin-top:.6rem">
            <div class="field" style="margin:0">
              <textarea wire:model="replyBody" rows="2" placeholder="Votre réponse…"></textarea>
              @error('replyBody') <span class="inline-err">{{ $message }}</span> @enderror
            </div>
            <div style="text-align:right;margin-top:.5rem">
              <button class="btn sm" wire:click="sendReply">Envoyer la réponse</button>
            </div>
          </div>
        @endif
      </div>
    @empty
      <div class="card"><div class="empty"><p>Aucune question pour l'instant — lancez la discussion !</p></div></div>
    @endforelse
  </div>

</div>

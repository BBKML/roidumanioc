<div style="display:flex;flex-direction:column;gap:1.4rem">

  <div class="page-intro">
    <div>
      <a class="link-btn" href="{{ route('learner.dashboard') }}" wire:navigate>← Mon espace</a>
      <h2>{{ $formation->title }}</h2>
      <p>{{ $progress['done'] }}/{{ $progress['total'] }} leçons terminées · {{ $progress['pct'] }}%</p>
    </div>
  </div>

  <div class="progress" style="max-width:420px"><i style="width:{{ $progress['pct'] }}%"></i></div>

  @if (! $current)
    <div class="card"><div class="empty"><p>Cette formation n'a pas encore de leçon.</p></div></div>
  @else
    <div class="course-layout">
      <div class="lesson-list">
        <div class="lh"><b>Contenu de la formation</b></div>
        @foreach ($lessons as $lesson)
          <button wire:key="ll-{{ $lesson->id }}"
                  class="{{ $lesson->id === $current->id ? 'on ' : '' }}{{ $doneIds->contains($lesson->id) ? 'done' : '' }}"
                  wire:click="select({{ $lesson->id }})">
            <span class="tick">
              @if ($doneIds->contains($lesson->id))
                <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M20 6 9 17l-5-5"/></svg>
              @endif
            </span>
            <span class="lm">{{ $lesson->title }}<span>{{ $lesson->type->label() }} · {{ $lesson->duration_label ?: '—' }}</span></span>
          </button>
        @endforeach
      </div>

      <div>
        @php
          $kind = $current->mediaKind();
          $premium = ! $formation->isFree();
          $u = auth()->user();
          $mark = $u->name.' · '.\Illuminate\Support\Str::mask((string) $u->phone, '•', 2, max(0, strlen((string) $u->phone) - 4));
        @endphp

        @if ($kind === 'embed')
          <div style="aspect-ratio:16/9;border-radius:var(--r);overflow:hidden;margin-bottom:1.2rem;background:#000">
            <iframe src="{{ $current->embedUrl() }}" style="width:100%;height:100%;border:0" allowfullscreen allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" title="{{ $current->title }}"></iframe>
          </div>
        @elseif ($kind === 'file')
          <div class="lesson-video-wrap">
            <video src="{{ $current->videoStreamUrl() }}" controls controlsList="nodownload" preload="metadata"
                   oncontextmenu="return false" style="width:100%;height:100%;display:block;background:#000"></video>
            @if ($premium && $u->phone)<span class="video-watermark">{{ $mark }}</span>@endif
          </div>
        @elseif ($kind === 'document')
          <div style="aspect-ratio:16/9;border-radius:var(--r);overflow:hidden;margin-bottom:1.2rem;border:1px solid var(--sand)">
            <iframe src="{{ $current->primaryDocument()->downloadUrl() }}#toolbar=0" style="width:100%;height:100%;border:0" title="{{ $current->title }}"></iframe>
          </div>
        @else
          <div class="video">
            <span class="play"><svg class="ic" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg></span>
          </div>
        @endif

        <div class="lesson-body">
          <span class="pill neutral"><span class="dot"></span>Leçon {{ $currentIndex + 1 }} sur {{ $lessons->count() }}</span>
          <h3>{{ $current->title }}</h3>

          @if (filled($current->content))
            {!! nl2br(e($current->content)) !!}
          @elseif ($kind === 'none')
            <p class="muted">Le contenu détaillé de cette leçon sera bientôt disponible.</p>
          @endif

          @php $resources = $current->attachments->reject(fn ($a) => $kind === 'document' && $a->is($current->primaryDocument())); @endphp
          @if ($resources->isNotEmpty())
            <div class="lesson-resources">
              <b>Ressources à télécharger</b>
              @foreach ($resources as $a)
                <a href="{{ $a->downloadUrl() }}" target="_blank" rel="noopener">
                  <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2h9l5 5v15H6z"/><path d="M14 2v6h6M9 14h6M9 18h6"/></svg>
                  <span>{{ $a->title }}</span>
                  <span class="muted">{{ $a->humanSize() }}</span>
                </a>
              @endforeach
            </div>
          @endif

          <div class="lesson-nav">
            <button class="btn ghost" wire:click="go('prev')" @disabled($currentIndex === 0)>← Leçon précédente</button>
            <button class="btn {{ $doneIds->contains($current->id) ? 'ghost' : '' }}" wire:click="toggleComplete">
              @if ($doneIds->contains($current->id))
                Marqué comme terminé ✓
              @else
                <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M20 6 9 17l-5-5"/></svg>
                Marquer comme terminé
              @endif
            </button>
            <button class="btn ghost" wire:click="go('next')" @disabled($currentIndex === $lessons->count() - 1)>Leçon suivante →</button>
          </div>
        </div>
      </div>
    </div>
  @endif

</div>

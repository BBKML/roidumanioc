<div style="display:flex;flex-direction:column;gap:1.4rem">

  <div class="page-intro">
    <div>
      <a class="link-btn" href="{{ route('admin.formations') }}" wire:navigate>← Formations</a>
      <h2>{{ $formation->title }}</h2>
      <p>{{ $lessons->count() }} leçon{{ $lessons->count() > 1 ? 's' : '' }} · {{ $formation->category }} ·
        {{ $formation->isFree() ? 'Gratuit' : number_format($formation->price, 0, ',', ' ').' FCFA' }}</p>
    </div>
    <button class="btn" wire:click="new">
      <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
      Ajouter une leçon
    </button>
  </div>

  @if ($lessons->isEmpty())
    <div class="card"><div class="empty">
      <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4v16l7-2 7 2V4l-7 2-7-2z"/></svg>
      <p>Aucune leçon pour l'instant.</p>
    </div></div>
  @else
    <div class="table-wrap">
      <table>
        <thead><tr><th>#</th><th>Titre de la leçon</th><th>Type</th><th>Média</th><th>Durée</th><th>Ordre</th><th></th></tr></thead>
        <tbody>
          @foreach ($lessons as $i => $lesson)
            <tr class="row" wire:key="lesson-{{ $lesson->id }}">
              <td class="nums">{{ $i + 1 }}</td>
              <td style="font-weight:700">{{ $lesson->title }}</td>
              <td>
                @php $t = $lesson->type->value; @endphp
                <span class="pill {{ $t === 'quiz' ? 'info' : ($t === 'document' ? 'warn' : 'neutral') }}"><span class="dot"></span>{{ $lesson->type->label() }}</span>
              </td>
              <td class="muted" style="font-size:.8rem">
                @switch($lesson->video_provider)
                  @case('link') Lien externe @break
                  @case('bunny') Bunny Stream @break
                  @case('upload') Fichier téléversé @break
                  @default {{ $lesson->attachments_count ? '' : '—' }}
                @endswitch
                @if ($lesson->attachments_count)
                  <span class="pill neutral" style="margin-left:.3rem"><span class="dot"></span>{{ $lesson->attachments_count }} PDF</span>
                @endif
              </td>
              <td class="muted">{{ $lesson->duration_label ?: '—' }}</td>
              <td>
                <span class="reorder">
                  <button wire:click="move({{ $lesson->id }}, 'up')" @disabled($loop->first) title="Monter">▲</button>
                  <button wire:click="move({{ $lesson->id }}, 'down')" @disabled($loop->last) title="Descendre">▼</button>
                </span>
              </td>
              <td>
                <div class="icon-actions">
                  <button class="iact" wire:click="edit({{ $lesson->id }})" title="Modifier">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 20h4L20 8l-4-4L4 16v4z"/></svg>
                  </button>
                  <button class="iact danger" wire:click="delete({{ $lesson->id }})" data-confirm="Supprimer cette leçon et ses ressources ?" title="Supprimer">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M9 7V5h6v2M6 7l1 13h10l1-13"/></svg>
                  </button>
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif

  <x-adm.modal :show="$showForm" :title="$editingId ? 'Modifier la leçon' : 'Nouvelle leçon'">
    <form wire:submit="save" id="lesson-form">
      <div class="field">
        <label>Titre de la leçon</label>
        <input type="text" wire:model="title">
        @error('title') <span class="inline-err">{{ $message }}</span> @enderror
      </div>
      <div class="field-row">
        <div class="field">
          <label>Type de leçon</label>
          <select wire:model.live="type">
            <option value="video">Vidéo</option>
            <option value="document">Document (PDF)</option>
            <option value="quiz">Quiz</option>
          </select>
        </div>
        <div class="field">
          <label>Durée affichée</label>
          <input type="text" wire:model="duration_label" placeholder="12 min">
          @error('duration_label') <span class="inline-err">{{ $message }}</span> @enderror
        </div>
      </div>

      @if ($type === 'video')
        <div class="field">
          <label>Source de la vidéo</label>
          <div class="tabs" style="margin-bottom:.5rem">
            <button type="button" class="{{ $videoProvider === 'none' ? 'on' : '' }}" wire:click="$set('videoProvider', 'none')">Aucune</button>
            <button type="button" class="{{ $videoProvider === 'link' ? 'on' : '' }}" wire:click="$set('videoProvider', 'link')">Lien</button>
            <button type="button" class="{{ $videoProvider === 'upload' ? 'on' : '' }}" wire:click="$set('videoProvider', 'upload')">Téléverser</button>
            <button type="button" class="{{ $videoProvider === 'bunny' ? 'on' : '' }}" wire:click="$set('videoProvider', 'bunny')">Bunny Stream</button>
          </div>

          @if ($videoProvider === 'link')
            <input type="url" wire:model.blur="videoUrl" placeholder="https://youtu.be/… ou vimeo.com/…">
            <span class="hint">YouTube ou Vimeo. Pour un cours payant, préférez « Téléverser » ou Bunny Stream.</span>
          @elseif ($videoProvider === 'bunny')
            <input type="text" wire:model.blur="videoUrl" placeholder="GUID Bunny ou https://iframe.mediadelivery.net/embed/…">
            <span class="hint">Lecture signée et non partageable si la clé Bunny est configurée.</span>
          @elseif ($videoProvider === 'upload')
            <input type="file" wire:model="videoUpload" accept="video/mp4,video/webm,video/quicktime">
            <div wire:loading wire:target="videoUpload" class="muted" style="font-size:.78rem;margin-top:.3rem">Téléversement…</div>
            @if ($hasStoredVideo && ! $videoUpload)
              <span class="hint">Une vidéo est déjà enregistrée. Choisissez un fichier pour la remplacer.</span>
            @else
              <span class="hint">MP4 / WebM, {{ $videoMaxMb }} Mo max. Fichier privé, servi uniquement aux inscrits.</span>
            @endif
          @endif
          @error('videoUrl') <span class="inline-err">{{ $message }}</span> @enderror
          @error('videoUpload') <span class="inline-err">{{ $message }}</span> @enderror
        </div>
      @endif

      <div class="field">
        <label>{{ $type === 'document' ? 'Introduction (avant le PDF)' : 'Contenu / notes de la leçon' }}</label>
        <textarea wire:model="content" rows="4"></textarea>
        @error('content') <span class="inline-err">{{ $message }}</span> @enderror
      </div>

      {{-- Ressources jointes (toujours facultatives) --}}
      <div style="border-top:1px solid var(--sand);margin-top:.5rem;padding-top:.9rem">
        <h4 style="font-size:.7rem;text-transform:uppercase;letter-spacing:.06em;color:var(--ink-soft);margin:0 0 .6rem">
          {{ $type === 'document' ? 'Document(s) de la leçon' : 'Ressources jointes (PDF, fiches…) — facultatif' }}
        </h4>

        @if (! $editingId)
          <p class="muted" style="font-size:.8rem">Enregistrez d'abord la leçon pour y joindre des fichiers.</p>
        @else
          @forelse ($editingAttachments as $a)
            <div class="cms-item" style="display:flex;justify-content:space-between;align-items:center" wire:key="att-{{ $a->id }}">
              <span>{{ $a->title }} <span class="muted">· {{ $a->humanSize() }}{{ $a->isPdf() ? ' · PDF' : '' }}</span></span>
              <button type="button" class="btn sm danger" wire:click="removeAttachment({{ $a->id }})" data-confirm="Retirer cette ressource ?">Retirer</button>
            </div>
          @empty
            <p class="muted" style="font-size:.8rem">Aucune ressource.</p>
          @endforelse

          <div class="field-row" style="margin-top:.6rem;align-items:end">
            <div class="field" style="margin:0">
              <label>Intitulé (facultatif)</label>
              <input type="text" wire:model="attachmentTitle" placeholder="repris du nom du fichier si vide">
              @error('attachmentTitle') <span class="inline-err">{{ $message }}</span> @enderror
            </div>
            <div class="field" style="margin:0">
              <label>Fichier ({{ $attachmentMaxMb }} Mo max)</label>
              <input type="file" wire:model="attachmentFile" accept="application/pdf,image/*">
              @error('attachmentFile') <span class="inline-err">{{ $message }}</span> @enderror
            </div>
          </div>
          <button type="button" class="btn ghost sm" style="margin-top:.5rem" wire:click="addAttachment"
                  wire:loading.attr="disabled" wire:target="attachmentFile,addAttachment">
            <span wire:loading.remove wire:target="attachmentFile">+ Ajouter la ressource</span>
            <span wire:loading wire:target="attachmentFile">Téléversement…</span>
          </button>
        @endif
      </div>
    </form>

    <x-slot:footer>
      <button type="button" class="btn ghost" wire:click="$set('showForm', false)">Fermer</button>
      <button type="submit" form="lesson-form" class="btn" wire:loading.attr="disabled" wire:target="save,videoUpload">Enregistrer la leçon</button>
    </x-slot:footer>
  </x-adm.modal>

</div>

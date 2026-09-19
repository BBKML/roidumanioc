<div class="rfb">

  <div class="rfb-toolbar">
    <a class="rfb-back" href="{{ route('admin.registration-forms') }}" wire:navigate title="Retour aux formulaires">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m15 18-6-6 6-6"/></svg>
    </a>

    <div class="rfb-title-group">
      <input type="text" form="rfb-form" wire:model.live.debounce.500ms="title" class="rfb-title-input" placeholder="Titre du formulaire (ex : Master Class Manioc — session novembre)">
      <div class="rfb-title-meta">
        <span class="rfb-slug">/inscription/<b>{{ $slug ?: '…' }}</b></span>
        @error('title') <span class="rfb-slug-err">{{ $message }}</span> @enderror
        @error('slug') <span class="rfb-slug-err">{{ $message }}</span> @enderror
      </div>
    </div>

    <div class="rfb-status">
      <button type="button" wire:click="$set('status', 'brouillon')" class="{{ $status === 'brouillon' ? 'on' : '' }}">Brouillon</button>
      <button type="button" wire:click="$set('status', 'publiee')" class="{{ $status === 'publiee' ? 'on' : '' }}">Publié</button>
      @error('status') <span class="rfb-slug-err">{{ $message }}</span> @enderror
    </div>

    @if ($justSaved)
      <span class="rfb-saved">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M20 6 9 17l-5-5"/></svg>
        Enregistré
      </span>
    @endif

    <div class="rfb-toolbar-actions">
      @if (! $isNew)
        <button type="button" class="iact" title="Copier le lien public" data-copy="{{ route('inscription.show', $slug) }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v1"/></svg>
        </button>
        @if ($status === 'publiee')
          <a class="iact" href="{{ route('inscription.show', $slug) }}" target="_blank" rel="noopener" title="Ouvrir la page publique">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M14 3h7v7"/><path d="M10 14 21 3"/><path d="M21 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h5"/></svg>
          </a>
        @endif
      @endif
      <button type="button" class="iact {{ $showPreview ? 'on' : '' }}" title="{{ $showPreview ? 'Masquer' : 'Afficher' }} l'aperçu" wire:click="togglePreview">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
      </button>
      <button type="submit" form="rfb-form" class="btn" wire:loading.attr="disabled" wire:target="save,coverImage">Enregistrer</button>
    </div>
  </div>

  <div class="rfb-layout {{ $showPreview ? '' : 'no-preview' }}">
    <form wire:submit="save" id="rfb-form" class="rfb-canvas">

      <div class="rfb-card">
        <div class="rfb-card-top"></div>
        <div class="rfb-card-body">
          <div class="field">
            <label>Sous-titre (affiché sous le titre, en badge)</label>
            <input type="text" wire:model.live.debounce.500ms="subtitle" placeholder="Ex : 3 jours + 12 mois d'accompagnement · 06-08 novembre 2026">
            @error('subtitle') <span class="inline-err">{{ $message }}</span> @enderror
          </div>
          <div class="field">
            <label>Présentation courte (introduction)</label>
            <textarea wire:model.live.debounce.500ms="intro" rows="3" placeholder="Ce que c'est, pour qui, la transformation recherchée…"></textarea>
            @error('intro') <span class="inline-err">{{ $message }}</span> @enderror
          </div>
          <div class="field">
            <label>Numéro WhatsApp de contact (facultatif)</label>
            <input type="text" wire:model.live.debounce.500ms="whatsapp_number" placeholder="+2250700000000">
            @error('whatsapp_number') <span class="inline-err">{{ $message }}</span> @enderror
          </div>
        </div>
      </div>

      <div class="rfb-card">
        <div class="rfb-card-top"></div>
        <div class="rfb-card-body">
          <div class="field">
            <label>Affiche / visuel de la formation (image)</label>
            @if ($coverImage)
              <img src="{{ $coverImage->temporaryUrl() }}" alt="" style="max-width:220px;border-radius:10px;margin-bottom:.5rem">
            @elseif ($cover_image_path)
              <img src="{{ $this->coverImagePreviewUrl() }}" alt="" style="max-width:220px;border-radius:10px;margin-bottom:.5rem">
            @endif
            <input type="file" wire:model="coverImage" accept="image/*">
            <div wire:loading wire:target="coverImage" class="muted" style="font-size:.78rem;margin-top:.3rem">Téléversement…</div>
            @error('coverImage') <span class="inline-err">{{ $message }}</span> @enderror
            @if ($coverImage || $cover_image_path)
              <button type="button" class="btn sm ghost" style="margin-top:.5rem;align-self:flex-start" wire:click="removeCoverImage">Retirer l'image</button>
            @endif
          </div>
        </div>
      </div>

      <div class="rfb-card is-content">
        <div class="rfb-card-top"></div>
        <div class="rfb-card-body">
          <h3 class="rfb-card-heading"><span class="rfb-num">1</span> Pourquoi cette formation</h3>
          @include('livewire.admin.partials.registration-form-items', [
            'field' => 'objectives', 'label' => 'Objectifs',
            'hint' => 'Un point par ligne. Mettez un mot-clé « en gras » si besoin (ex : le verbe d\'action). Ajoutez un sous-titre pour introduire une opportunité de marché avant les objectifs.',
            'items' => $items['objectives'] ?? [],
          ])
        </div>
      </div>

      <div class="rfb-card is-content">
        <div class="rfb-card-top"></div>
        <div class="rfb-card-body">
          <h3 class="rfb-card-heading"><span class="rfb-num">2</span> Le format</h3>
          @include('livewire.admin.partials.registration-form-items', [
            'field' => 'schedule_info', 'label' => 'Durée, dates, déroulement',
            'hint' => 'Ex : un point « Dates : » en gras suivi du texte, puis un sous-titre « Atouts » avant les points suivants.',
            'items' => $items['schedule_info'] ?? [],
          ])
        </div>
      </div>

      <div class="rfb-card is-content">
        <div class="rfb-card-top"></div>
        <div class="rfb-card-body">
          <h3 class="rfb-card-heading"><span class="rfb-num">3</span> Programme</h3>
          @include('livewire.admin.partials.registration-form-items', [
            'field' => 'program', 'label' => 'Programme',
            'hint' => 'Utilisez un sous-titre par module (ex : « Module 1 — Production ») puis les points de ce module en dessous.',
            'items' => $items['program'] ?? [],
          ])

          <p class="rfb-sub-heading">Pour qui est-ce fait ?</p>
          @include('livewire.admin.partials.registration-form-items', [
            'field' => 'certifications', 'label' => 'Profils concernés',
            'hint' => 'Affiché juste après le programme, sous « Cette formation est faite pour vous si : ».',
            'items' => $items['certifications'] ?? [],
          ])
        </div>
      </div>

      <div class="rfb-card is-content">
        <div class="rfb-card-top"></div>
        <div class="rfb-card-body">
          <h3 class="rfb-card-heading"><span class="rfb-num">4</span> Votre investissement</h3>
          <div class="field">
            <label>Tarif affiché</label>
            <input type="text" wire:model.live.debounce.500ms="price_amount" placeholder="200 000 FCFA">
            @error('price_amount') <span class="inline-err">{{ $message }}</span> @enderror
          </div>
          @include('livewire.admin.partials.registration-form-items', [
            'field' => 'price_note', 'label' => 'Détail (ce que le tarif inclut, bonus, tranches…)',
            'hint' => 'Ex : un sous-titre « En payant en une seule fois » puis les bonus, un sous-titre « En payant en plusieurs tranches » puis les conditions.',
            'items' => $items['price_note'] ?? [],
          ])

          @include('livewire.admin.partials.registration-form-items', [
            'field' => 'payment_methods', 'label' => 'Moyens de paiement', 'divider' => true,
            'hint' => 'Un moyen de paiement par ligne.',
            'items' => $items['payment_methods'] ?? [],
          ])

          <div class="field" style="border-top:1px dashed var(--sand);padding-top:1rem;margin-top:.2rem">
            <label>Offre « premiers inscrits » — échéance du compte à rebours (facultatif)</label>
            <p class="muted" style="font-size:.78rem;margin:0 0 .5rem">
              Laissez vide pour ne pas afficher de compte à rebours. Une fois l'heure passée, la page
              affiche automatiquement « Offre terminée » à la place du décompte.
            </p>
            <input type="datetime-local" wire:model.live.debounce.500ms="early_bird_deadline">
            @error('early_bird_deadline') <span class="inline-err">{{ $message }}</span> @enderror
          </div>

          <div class="field">
            <label>Réservation de place — montant de l'acompte (facultatif)</label>
            <p class="muted" style="font-size:.78rem;margin:0 0 .5rem">
              Laissez vide pour ne proposer que le paiement intégral. Renseigné, la page affiche les
              deux options côte à côte (intégral / réservation).
            </p>
            <input type="text" wire:model.live.debounce.500ms="deposit_amount" placeholder="50 000 FCFA">
            @error('deposit_amount') <span class="inline-err">{{ $message }}</span> @enderror
          </div>

          <div class="field">
            <label>Réservation de place — précisions (facultatif)</label>
            <p class="muted" style="font-size:.78rem;margin:0 0 .5rem">
              Ex : conditions de règlement du solde, éligibilité aux bonus. Affiché sous le montant de
              l'acompte.
            </p>
            <textarea wire:model.live.debounce.500ms="deposit_note" rows="3" placeholder="Le solde de 150 000 FCFA est à régler selon les modalités convenues avec l'équipe. Les bonus de l'offre premiers inscrits restent soumis aux conditions ci-dessus."></textarea>
            @error('deposit_note') <span class="inline-err">{{ $message }}</span> @enderror
          </div>
        </div>
      </div>
    </form>

    <div class="rfb-preview">
      <div class="rfb-preview-head">
        <p class="rfb-preview-label">Aperçu en direct — exactement la page publique.</p>
        <div class="rfb-preview-switch">
          <button type="button" wire:click="$set('previewWidth', 'mobile')" class="{{ $previewWidth === 'mobile' ? 'on' : '' }}">Mobile</button>
          <button type="button" wire:click="$set('previewWidth', 'desktop')" class="{{ $previewWidth === 'desktop' ? 'on' : '' }}">Web</button>
        </div>
      </div>
      <div class="rfb-preview-frame {{ $previewWidth === 'mobile' ? 'is-mobile' : '' }}">
        <iframe srcdoc="{{ $this->previewHtml() }}" title="Aperçu du formulaire" loading="lazy"></iframe>
      </div>
    </div>
  </div>

</div>

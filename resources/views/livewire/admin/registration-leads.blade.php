<div style="display:flex;flex-direction:column;gap:1.4rem">

  <div class="page-intro">
    <div>
      <h2>Prospects</h2>
      <p>Inscrits via vos <a href="{{ route('admin.registration-forms') }}" wire:navigate>formulaires publics</a>
        (réseaux sociaux). Suivez-les jusqu'à l'inscription confirmée.</p>
    </div>
    <div style="display:flex;gap:.6rem;flex-wrap:wrap">
      <a class="btn ghost" href="{{ route('admin.registration-leads.export', array_filter(['form' => $formId, 'status' => $filter, 'search' => $search])) }}">
        <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 4v12M6 10l6 6 6-6M4 20h16"/></svg>
        Exporter (CSV)
      </a>
      <button class="btn ghost" wire:click="openImport">
        <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 16V4M7 9l5-5 5 5"/><path d="M4 16v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"/></svg>
        Importer une liste
      </button>
    </div>
  </div>

  <div style="display:flex;gap:.8rem;flex-wrap:wrap;align-items:center">
    <label class="search" style="background:var(--paper)">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
      <input type="search" wire:model.live.debounce.300ms="search" placeholder="Nom, e-mail, téléphone…">
    </label>
    <select wire:model.live="formId" style="max-width:220px">
      <option value="">Tous les formulaires</option>
      @foreach ($forms as $form)
        <option value="{{ $form->id }}">{{ $form->title }}</option>
      @endforeach
    </select>
    <div class="tabs">
      <button class="{{ $filter === 'nouveau' ? 'on' : '' }}" wire:click="setFilter('nouveau')">Nouveaux ({{ $counts['nouveau'] }})</button>
      <button class="{{ $filter === 'contacte' ? 'on' : '' }}" wire:click="setFilter('contacte')">Contactés ({{ $counts['contacte'] }})</button>
      <button class="{{ $filter === 'inscrit' ? 'on' : '' }}" wire:click="setFilter('inscrit')">Inscrits ({{ $counts['inscrit'] }})</button>
      <button class="{{ $filter === 'abandonne' ? 'on' : '' }}" wire:click="setFilter('abandonne')">Abandonnés ({{ $counts['abandonne'] }})</button>
      <button class="{{ $filter === 'tous' ? 'on' : '' }}" wire:click="setFilter('tous')">Tous</button>
    </div>
    <x-adm.loading-note target="search,setFilter,formId" />
  </div>

  <div style="display:flex;flex-direction:column;gap:.7rem">
    @forelse ($leads as $lead)
      <div class="card" wire:key="lead-{{ $lead->id }}" style="{{ $lead->status->value === 'nouveau' ? 'border-color:var(--leaf)' : '' }}">
        <div style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;align-items:flex-start">
          <button class="link-btn" style="text-align:left" wire:click="open({{ $lead->id }})">
            <b style="font-size:.95rem">{{ $lead->fullName() }}</b>
            <div class="muted" style="font-weight:500;font-size:.82rem">
              {{ $lead->phone_1 }}@if ($lead->email) · {{ $lead->email }}@endif · {{ $lead->registrationForm->title }}
            </div>
          </button>
          <div style="display:flex;gap:.5rem;align-items:center;flex-wrap:wrap">
            <span class="muted" style="font-size:.76rem">{{ $lead->created_at->diffForHumans() }}</span>
            <x-adm.pill :status="$lead->status" />
          </div>
        </div>

        @if ($openId === $lead->id)
          <div style="margin-top:.9rem;border-top:1px solid var(--sand);padding-top:.9rem">
            <div class="table-wrap" style="border:none">
              <table style="font-size:.85rem">
                <tbody>
                  <tr><td class="muted">Genre</td><td>{{ $lead->gender ?: '—' }}</td></tr>
                  <tr><td class="muted">Statut / fonction</td><td>{{ $lead->profession ?: '—' }}</td></tr>
                  <tr><td class="muted">Niveau d'expérience</td><td>{{ $lead->experience_level ?: '—' }}</td></tr>
                  <tr><td class="muted">Tranche d'âge</td><td>{{ $lead->age_range ?: '—' }}</td></tr>
                  <tr><td class="muted">Entreprise / organisation</td><td>{{ $lead->company ?: '—' }}</td></tr>
                  <tr><td class="muted">Ville et pays</td><td>{{ $lead->city_country ?: '—' }}</td></tr>
                  <tr><td class="muted">Téléphone 2</td><td>{{ $lead->phone_2 ?: '—' }}</td></tr>
                  <tr><td class="muted">WhatsApp</td><td>{{ $lead->whatsapp ?: '—' }}</td></tr>
                  <tr><td class="muted">Indicatif pays</td><td>{{ $lead->country_code ?: '—' }}</td></tr>
                  <tr><td class="muted">Motivation(s)</td><td>{{ $lead->motivations ?: '—' }}</td></tr>
                  <tr><td class="muted">Attentes</td><td>{{ $lead->expectations ?: '—' }}</td></tr>
                  <tr><td class="muted">Informé par</td><td>{{ $lead->how_heard ?: '—' }}</td></tr>
                  <tr><td class="muted">Paiement souhaité</td><td>{{ $lead->payment_method ?: '—' }}@if($lead->payment_frequency) · {{ $lead->payment_frequency }}@endif</td></tr>
                </tbody>
              </table>
            </div>

            <div class="row-actions" style="justify-content:flex-start;margin:1rem 0">
              @if ($lead->mailtoLink())
                <a class="btn sm ghost" href="{{ $lead->mailtoLink() }}">Écrire un e-mail</a>
              @endif
              @if ($lead->whatsappLink())
                <a class="btn sm ghost" href="{{ $lead->whatsappLink() }}" target="_blank" rel="noopener">WhatsApp</a>
              @endif
              @if ($lead->status->value !== 'contacte')
                <button class="btn sm" wire:click="markContacted({{ $lead->id }})">Marquer contacté</button>
              @endif
              @if ($lead->status->value !== 'inscrit')
                <button class="btn sm" wire:click="markEnrolled({{ $lead->id }})">Marquer inscrit</button>
              @endif
              @if ($lead->status->value !== 'abandonne')
                <button class="btn sm danger" wire:click="markAbandoned({{ $lead->id }})" data-confirm="Marquer ce prospect comme abandonné ?">Abandonné</button>
              @else
                <button class="btn sm ghost" wire:click="reopen({{ $lead->id }})">Rouvrir</button>
              @endif
              <button class="iact danger" wire:click="delete({{ $lead->id }})" data-confirm="Supprimer définitivement ce prospect ?" title="Supprimer">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M9 7V5h6v2M6 7l1 13h10l1-13"/></svg>
              </button>
            </div>

            <div class="field" style="margin:0">
              <label>Note interne</label>
              <textarea wire:model="note" rows="2" placeholder="Suivi, contexte…"></textarea>
            </div>
            <button class="btn sm ghost" style="margin-top:.4rem" wire:click="saveNote({{ $lead->id }})">Enregistrer la note</button>

            @if ($lead->handledBy)
              <p class="muted" style="font-size:.76rem;margin:.8rem 0 0">Suivi par {{ $lead->handledBy->name }} le {{ $lead->handled_at?->format('d/m/Y H:i') }}</p>
            @endif
          </div>
        @endif
      </div>
    @empty
      <div class="card"><div class="empty"><p>Aucun prospect dans cette vue.</p></div></div>
    @endforelse
  </div>

  {{ $leads->links() }}

  <x-adm.modal :show="$showImport" title="Importer une liste de prospects" close="$set('showImport', false)">
    <form wire:submit="import" id="import-leads-form">
      <div class="field">
        <label>Campagne</label>
        <select wire:model="importFormId">
          <option value="">— Choisir —</option>
          @foreach ($forms as $form)
            <option value="{{ $form->id }}">{{ $form->title }}</option>
          @endforeach
        </select>
        @error('importFormId') <span class="inline-err">{{ $message }}</span> @enderror
      </div>
      <div class="field">
        <label>Fichier CSV</label>
        <input type="file" wire:model="importFile" accept=".csv,text/csv">
        <div wire:loading wire:target="importFile" class="muted" style="font-size:.78rem">Téléversement…</div>
        @error('importFile') <span class="inline-err">{{ $message }}</span> @enderror
        <p class="muted" style="font-size:.78rem;margin-top:.4rem">
          Colonnes reconnues : genre, nom, prenoms, email, telephone, whatsapp, profession,
          tranche_age, ville_pays, entreprise, moyen_paiement. Nom, prénoms et téléphone sont
          obligatoires — les autres colonnes sont facultatives.
          <a href="{{ route('admin.registration-leads.template') }}">Télécharger un modèle</a>.
        </p>
      </div>
    </form>

    @if ($importResult)
      <div class="card" style="margin-top:1rem">
        <p>
          <b>{{ $importResult['created'] }}</b> prospect(s) importé(s)
          @if ($importResult['duplicates'])
            , <b>{{ $importResult['duplicates'] }}</b> déjà inscrit(s) ignoré(s)
          @endif
          .
        </p>
        @if (count($importResult['errors']))
          <p class="muted" style="font-size:.82rem;margin-top:.6rem">Lignes ignorées :</p>
          <ul style="font-size:.82rem;margin:.3rem 0 0 1.1rem">
            @foreach ($importResult['errors'] as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        @endif
      </div>
    @endif

    <x-slot:footer>
      <button type="button" class="btn ghost" wire:click="$set('showImport', false)">Fermer</button>
      <button type="submit" form="import-leads-form" class="btn" wire:loading.attr="disabled" wire:target="import,importFile">Importer</button>
    </x-slot:footer>
  </x-adm.modal>

</div>

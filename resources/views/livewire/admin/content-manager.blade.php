<div style="display:flex;flex-direction:column;gap:1rem">

  <div class="page-intro">
    <div>
      <h2>Contenu du site public</h2>
      <p>Modifiez ici tous les <b>textes, titres et images</b> de la vitrine. Chaque bloc correspond à une section de la page d'accueil. L'enregistrement met le site à jour immédiatement.</p>
    </div>
    <a class="btn ghost" href="{{ route('home') }}" target="_blank" rel="noopener">
      <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
      Voir le site
    </a>
  </div>

  @foreach ($sections as $key => $section)
    <details class="cms-block" wire:key="cms-{{ $key }}" @if($open === $key) open @endif>
      <summary wire:click.prevent="toggle(@js($key))">
        {{ $section['label'] }}
        <span class="muted">{{ $section['hint'] }}</span>
      </summary>
      <div class="cms-body">

        @foreach ($section['fields'] as [$name, $type, $label])
          <x-adm.cms-field :path="'data.'.$key.'.'.$name" :type="$type" :label="$label" />
        @endforeach

        @isset($section['repeater'])
          @php $rep = $section['repeater']; $items = data_get($data, $key.'.'.$rep['path'], []); @endphp
          <h4>{{ \Illuminate\Support\Str::plural($rep['label']) }}</h4>
          @foreach ($items as $i => $item)
            <div class="cms-item" wire:key="rep-{{ $key }}-{{ $i }}">
              <div class="cms-item-head">{{ $rep['label'] }} {{ $i + 1 }}</div>
              @foreach ($rep['fields'] as [$fname, $ftype, $flabel])
                <x-adm.cms-field :path="'data.'.$key.'.'.$rep['path'].'.'.$i.'.'.$fname" :type="$ftype" :label="$flabel" />
              @endforeach
            </div>
          @endforeach
        @endisset

        <button class="btn cms-save" wire:click="save(@js($key))">
          <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M20 6 9 17l-5-5"/></svg>
          Enregistrer « {{ $section['label'] }} »
        </button>
      </div>
    </details>
  @endforeach

  {{-- Témoignages (modèle dédié) --}}
  <details class="cms-block" wire:key="cms-testimonials" @if($open === 'testimonials') open @endif>
    <summary wire:click.prevent="toggle('testimonials')">
      Témoignages <span class="muted">section Communauté — citations des membres</span>
    </summary>
    <div class="cms-body">
      @forelse ($testimonials as $i => $t)
        <div class="cms-item" wire:key="testi-{{ $t['id'] }}">
          <div class="cms-item-head">
            Témoignage {{ $i + 1 }}
            <button class="btn sm danger" wire:click="deleteTestimonial({{ $t['id'] }})" data-confirm="Supprimer ce témoignage ?">Retirer</button>
          </div>
          <div class="bilingual-grid">
            <div class="bilingual-cell"><span class="bilingual-tag">Français</span><textarea wire:model="testimonials.{{ $i }}.quote" rows="2"></textarea></div>
            <div class="bilingual-cell"><span class="bilingual-tag">English</span><textarea wire:model="testimonials.{{ $i }}.quote_en" rows="2"></textarea></div>
          </div>
          <div class="field-row">
            <div class="field"><label>Nom</label><input type="text" wire:model="testimonials.{{ $i }}.author_name"></div>
          </div>
          <div class="bilingual-grid">
            <div class="bilingual-cell"><span class="bilingual-tag">Rôle · ville (FR)</span><input type="text" wire:model="testimonials.{{ $i }}.author_role"></div>
            <div class="bilingual-cell"><span class="bilingual-tag">Role · city (EN)</span><input type="text" wire:model="testimonials.{{ $i }}.author_role_en"></div>
          </div>
          <button class="btn sm" wire:click="saveTestimonial({{ $i }})">Enregistrer ce témoignage</button>
        </div>
      @empty
        <p class="muted">Aucun témoignage.</p>
      @endforelse
      <button class="btn ghost sm" wire:click="addTestimonial">+ Ajouter un témoignage</button>
    </div>
  </details>

  {{-- Distinctions / prix (modèle dédié) --}}
  <details class="cms-block" wire:key="cms-awards" @if($open === 'awards') open @endif>
    <summary wire:click.prevent="toggle('awards')">
      Distinctions — liste des prix <span class="muted">section Reconnaissance</span>
    </summary>
    <div class="cms-body">
      @forelse ($awards as $i => $a)
        <div class="cms-item" wire:key="award-{{ $a['id'] }}">
          <div class="cms-item-head">
            Prix {{ $i + 1 }}
            <button class="btn sm danger" wire:click="deleteAward({{ $a['id'] }})" data-confirm="Supprimer cette distinction ?">Retirer</button>
          </div>
          <div class="field"><label>Année</label><input type="text" wire:model="awards.{{ $i }}.year" style="max-width:140px"></div>
          <div class="bilingual-grid">
            <div class="bilingual-cell"><span class="bilingual-tag">Intitulé (FR)</span><input type="text" wire:model="awards.{{ $i }}.title"></div>
            <div class="bilingual-cell"><span class="bilingual-tag">Title (EN)</span><input type="text" wire:model="awards.{{ $i }}.title_en"></div>
          </div>
          <div class="bilingual-grid">
            <div class="bilingual-cell"><span class="bilingual-tag">Description (FR)</span><textarea wire:model="awards.{{ $i }}.description" rows="2"></textarea></div>
            <div class="bilingual-cell"><span class="bilingual-tag">Description (EN)</span><textarea wire:model="awards.{{ $i }}.description_en" rows="2"></textarea></div>
          </div>
          <button class="btn sm" wire:click="saveAward({{ $i }})">Enregistrer cette distinction</button>
        </div>
      @empty
        <p class="muted">Aucune distinction.</p>
      @endforelse
      <button class="btn ghost sm" wire:click="addAward">+ Ajouter une distinction</button>
    </div>
  </details>

  {{-- Partenaires (modèle dédié, logos défilants sur /communaute) --}}
  <details class="cms-block" wire:key="cms-partners" @if($open === 'partners') open @endif>
    <summary wire:click.prevent="toggle('partners')">
      Partenaires <span class="muted">logos défilants — section Communauté</span>
    </summary>
    <div class="cms-body">
      @forelse ($partners as $i => $p)
        <div class="cms-item" wire:key="partner-{{ $p['id'] }}">
          <div class="cms-item-head">
            Partenaire {{ $i + 1 }}
            <button class="btn sm danger" wire:click="deletePartner({{ $p['id'] }})" data-confirm="Supprimer ce partenaire ?">Retirer</button>
          </div>
          <div class="img-field">
            @if ($partnerLogos[$i] ?? null)
              <img src="{{ $partnerLogos[$i]->temporaryUrl() }}" alt="">
            @else
              <img src="{{ $p['logo_path'] ? \Illuminate\Support\Facades\Storage::url($p['logo_path']) : '' }}" alt="" @style(['visibility:hidden' => ! $p['logo_path']]) onerror="this.style.visibility='hidden'">
            @endif
            <div class="grow">
              <input type="file" wire:model="partnerLogos.{{ $i }}" accept="image/*">
              <div wire:loading wire:target="partnerLogos.{{ $i }}" class="muted" style="font-size:.78rem">Téléversement…</div>
              @error('logo') <span class="inline-err">{{ $message }}</span> @enderror
              <div class="field"><label>Nom (texte alternatif du logo)</label><input type="text" wire:model="partners.{{ $i }}.name"></div>
            </div>
          </div>
          <button class="btn sm" wire:click="savePartner({{ $i }})">Enregistrer ce partenaire</button>
        </div>
      @empty
        <p class="muted">Aucun partenaire.</p>
      @endforelse
      <button class="btn ghost sm" wire:click="addPartner">+ Ajouter un partenaire</button>
    </div>
  </details>

</div>

<div style="display:flex;flex-direction:column;gap:1.4rem">

  <div class="page-intro">
    <div>
      <h2>Profil producteur</h2>
      <p>
        @if ($exists)
          Vos informations, visibles par l'équipe et les acheteurs.
        @else
          Activez votre profil producteur en le complétant.
        @endif
      </p>
    </div>
    @if ($exists)
      <x-adm.pill :status="$activity_type ? \App\Enums\ActivityType::from($activity_type) : null" />
    @endif
  </div>

  <form wire:submit="save" class="card pad-lg" style="max-width:640px">
    <div class="card-head"><h3>Profil producteur</h3></div>

    <div class="field">
      <label>Nom de l'exploitation / de l'activité</label>
      <input type="text" wire:model="business_name">
      @error('business_name') <span class="inline-err">{{ $message }}</span> @enderror
    </div>

    <div class="field-row">
      <div class="field">
        <label>Zone</label>
        <input type="text" wire:model="zone" placeholder="Ex. Daloa, Haut-Sassandra">
        @error('zone') <span class="inline-err">{{ $message }}</span> @enderror
      </div>
      <div class="field">
        <label>Type d'activité</label>
        <select wire:model="activity_type">
          <option value="">— Choisir —</option>
          @foreach (\App\Enums\ActivityType::cases() as $type)
            <option value="{{ $type->value }}">{{ $type->label() }}</option>
          @endforeach
        </select>
        @error('activity_type') <span class="inline-err">{{ $message }}</span> @enderror
      </div>
    </div>

    <div class="field">
      <label>Présentation</label>
      <textarea wire:model="bio" rows="4"></textarea>
      @error('bio') <span class="inline-err">{{ $message }}</span> @enderror
    </div>

    <div class="field-row">
      <div class="field">
        <label>Capacité de production <span class="muted">(facultatif)</span></label>
        <input type="text" wire:model="capacity_note" placeholder="Ex. 5 tonnes / mois">
        @error('capacity_note') <span class="inline-err">{{ $message }}</span> @enderror
      </div>
      <div class="field">
        <label>Années d'activité <span class="muted">(facultatif)</span></label>
        <input type="number" min="0" max="80" wire:model="years_active">
        @error('years_active') <span class="inline-err">{{ $message }}</span> @enderror
      </div>
    </div>

    <div class="field">
      <label>Logo <span class="muted">(facultatif)</span></label>
      @if ($logo)
        <img src="{{ $logo->temporaryUrl() }}" alt="" style="max-width:140px;border-radius:10px;margin-bottom:.5rem">
      @elseif ($logo_path)
        <img src="{{ \Illuminate\Support\Facades\Storage::url($logo_path) }}" alt="" style="max-width:140px;border-radius:10px;margin-bottom:.5rem">
      @endif
      <input type="file" wire:model="logo" accept="image/*">
      <div wire:loading wire:target="logo" class="muted" style="font-size:.78rem;margin-top:.3rem">Téléversement…</div>
      @error('logo') <span class="inline-err">{{ $message }}</span> @enderror
    </div>

    @unless ($exists)
      <div class="field">
        <label class="switch-row" style="margin:0;font-size:.84rem">
          <input type="checkbox" wire:model="acceptedTerms">
          J'accepte les <a href="{{ route('legal.notice') }}" target="_blank" rel="noopener">conditions générales d'utilisation</a>
          et la <a href="{{ route('legal.privacy') }}" target="_blank" rel="noopener">politique de confidentialité</a>.
        </label>
        @error('acceptedTerms') <span class="inline-err">{{ $message }}</span> @enderror
      </div>
    @endunless

    <button type="submit" class="btn" wire:loading.attr="disabled" wire:target="save,logo">
      {{ $exists ? 'Enregistrer' : 'Devenir producteur' }}
    </button>
  </form>

</div>

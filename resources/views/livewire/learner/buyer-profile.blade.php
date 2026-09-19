<div style="display:flex;flex-direction:column;gap:1.4rem">

  <div class="page-intro">
    <div>
      <h2>Profil acheteur</h2>
      <p>
        @if ($exists)
          Vos informations, visibles par l'équipe et les producteurs.
        @else
          Activez votre profil acheteur en le complétant.
        @endif
      </p>
    </div>
  </div>

  <form wire:submit="save" class="card pad-lg" style="max-width:640px">
    <div class="card-head"><h3>Profil acheteur</h3></div>

    <div class="field">
      <label>Entreprise / structure <span class="muted">(facultatif)</span></label>
      <input type="text" wire:model="company_name">
      @error('company_name') <span class="inline-err">{{ $message }}</span> @enderror
    </div>

    <div class="field-row">
      <div class="field">
        <label>Zone</label>
        <input type="text" wire:model="zone" placeholder="Ex. Abidjan, Cocody">
        @error('zone') <span class="inline-err">{{ $message }}</span> @enderror
      </div>
      <div class="field">
        <label>Profil d'achat</label>
        <select wire:model="buyer_type">
          <option value="">— Choisir —</option>
          @foreach (\App\Enums\BuyerType::cases() as $type)
            <option value="{{ $type->value }}">{{ $type->label() }}</option>
          @endforeach
        </select>
        @error('buyer_type') <span class="inline-err">{{ $message }}</span> @enderror
      </div>
    </div>

    <div class="field">
      <label>Présentation <span class="muted">(facultatif)</span></label>
      <textarea wire:model="bio" rows="4"></textarea>
      @error('bio') <span class="inline-err">{{ $message }}</span> @enderror
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

    <button type="submit" class="btn" wire:loading.attr="disabled" wire:target="save">
      {{ $exists ? 'Enregistrer' : 'Devenir acheteur' }}
    </button>
  </form>

</div>

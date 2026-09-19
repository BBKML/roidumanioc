@props(['path', 'type' => 'text', 'label' => ''])

@if ($type === 'image')
  @php
    $src = data_get($__livewire, $path.'.src');
    $imgPath = \Illuminate\Support\Str::after($path, 'data.');
    $pending = data_get($__livewire, 'imageFiles.'.$imgPath);
  @endphp
  <div class="field">
    <label>{{ $label }}</label>
    <div class="img-field">
      @if ($pending)
        <img src="{{ $pending->temporaryUrl() }}" alt="">
      @else
        <img src="{{ $src ? asset($src) : '' }}" alt="" @style(['visibility:hidden' => ! $src]) onerror="this.style.visibility='hidden'">
      @endif
      <div class="grow">
        <input type="file" wire:model="imageFiles.{{ $imgPath }}" accept="image/*">
        <div wire:loading wire:target="imageFiles.{{ $imgPath }}" class="muted" style="font-size:.78rem">Téléversement…</div>
        @error('image') <span class="inline-err">{{ $message }}</span> @enderror
        <input type="text" wire:model="{{ $path }}.alt" placeholder="Texte alternatif (SEO + accessibilité)">
        @if ($src || $pending)
          <button type="button" class="btn sm ghost" style="align-self:flex-start" wire:click="removeImage('{{ $path }}')">Retirer l'image</button>
        @endif
      </div>
    </div>
  </div>
@else
  @php
    $locales = config('locales.supported');
    $localeLabels = ['fr' => 'Français', 'en' => 'English'];
  @endphp
  <div class="field">
    <label>{{ $label }}</label>
    <div class="bilingual-grid">
      @foreach ($locales as $locale)
        <div class="bilingual-cell">
          <span class="bilingual-tag">{{ $localeLabels[$locale] ?? strtoupper($locale) }}</span>
          @if ($type === 'textarea' || $type === 'list')
            <textarea wire:model="{{ $path }}.{{ $locale }}" rows="{{ $type === 'list' ? 4 : 3 }}"></textarea>
          @else
            <input type="text" wire:model="{{ $path }}.{{ $locale }}">
          @endif
        </div>
      @endforeach
    </div>
    @if ($type === 'list')
      <span class="hint">Une entrée par ligne.</span>
    @elseif ($type === 'html')
      <span class="hint">Balises autorisées : &lt;br&gt;, &lt;em&gt;, &lt;strong&gt;.</span>
    @endif
  </div>
@endif

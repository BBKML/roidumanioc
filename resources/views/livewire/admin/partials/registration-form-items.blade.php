{{--
  Éditeur de points façon Google Forms : chaque ligne de contenu (objectifs, programme…)
  devient une entrée ajoutable/supprimable/réordonnable, avec un champ « en gras » séparé du
  texte — l'admin n'a plus jamais besoin d'écrire **gras** ou ## à la main.
  Attend : $field (clé dans items.*), $label, $hint (aide), $items (tableau courant),
  $divider (optionnel : ligne pointillée au-dessus, pour séparer d'une section précédente
  sans dupliquer le libellé dans un sous-titre à part).
--}}
<div class="field"@if ($divider ?? false) style="border-top:1px dashed var(--sand);padding-top:1rem;margin-top:.2rem" @endif>
  <label>{{ $label }}</label>
  @if ($hint ?? null)
    <p class="muted" style="font-size:.78rem;margin:0 0 .5rem">{{ $hint }}</p>
  @endif

  <div style="display:flex;flex-direction:column;gap:.5rem">
    @forelse ($items as $index => $item)
      <div class="rf-item{{ ($item['type'] ?? 'text') === 'heading' ? ' rf-item-heading' : '' }}" wire:key="{{ $field }}-item-{{ $index }}">
        @if (($item['type'] ?? 'text') === 'heading')
          <span class="rf-item-tag">Sous-titre</span>
          <input type="text" wire:model.live.debounce.500ms="items.{{ $field }}.{{ $index }}.text" placeholder="Ex : Atouts" class="rf-item-input">
        @else
          <input type="text" wire:model.live.debounce.500ms="items.{{ $field }}.{{ $index }}.label" placeholder="En gras (optionnel)" class="rf-item-input rf-item-label">
          <input type="text" wire:model.live.debounce.500ms="items.{{ $field }}.{{ $index }}.text" placeholder="Texte du point" class="rf-item-input rf-item-text">
        @endif
        <div class="rf-item-actions">
          <button type="button" class="iact" title="Monter" wire:click="moveItem('{{ $field }}', {{ $index }}, -1)" @disabled($index === 0)>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m18 15-6-6-6 6"/></svg>
          </button>
          <button type="button" class="iact" title="Descendre" wire:click="moveItem('{{ $field }}', {{ $index }}, 1)" @disabled($index === count($items) - 1)>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m6 9 6 6 6-6"/></svg>
          </button>
          <button type="button" class="iact danger" title="Supprimer" wire:click="removeItem('{{ $field }}', {{ $index }})">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
          </button>
        </div>
      </div>
    @empty
      <p class="muted" style="font-size:.82rem;margin:0">Aucun point pour l'instant.</p>
    @endforelse
  </div>

  <div style="display:flex;gap:.6rem;margin-top:.6rem">
    <button type="button" class="btn sm ghost" wire:click="addItem('{{ $field }}')">+ Ajouter un point</button>
    <button type="button" class="btn sm ghost" wire:click="addItem('{{ $field }}', 'heading')">+ Ajouter un sous-titre</button>
  </div>
</div>

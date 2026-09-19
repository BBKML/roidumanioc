{{--
  En-tête de colonne triable — réutilise le <th> existant (mêmes styles admin.css), pas un
  nouveau composant de tableau. Le composant Livewire hôte doit exposer $sort/$direction
  et une méthode sortBy($field) (toggle asc/desc, cf. Admin\Members/Orders/Producers).
--}}
@props(['field', 'sort', 'direction'])
<th wire:click="sortBy('{{ $field }}')" class="is-sortable @if($sort === $field) is-sorted @endif">
  <span class="th-sort">
    {{ $slot }}
    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="th-sort-ic @if($sort === $field && $direction === 'desc') is-desc @endif">
      <path d="M6 9l6 6 6-6"/>
    </svg>
  </span>
</th>

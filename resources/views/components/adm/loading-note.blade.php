{{--
  Indicateur discret de chargement pour les listes Livewire (recherche, filtres, tri) —
  même idiome texte que les indicateurs d'upload déjà présents ailleurs dans le back-office
  (ex. "Téléversement…" dans content-manager/events/lesson-manager), pas un nouveau système.
  `target` liste les noms de propriété/méthode à surveiller (recherche live, `$set('filter', …)`
  et/ou une méthode dédiée type `setFilter` selon l'écran) — les noms absents de l'écran sont
  simplement ignorés par wire:loading, aucune erreur.
--}}
@props(['target' => 'search,filter,setFilter,sort'])
<span wire:loading wire:target="{{ $target }}" class="muted" style="font-size:.78rem">Chargement…</span>

@props(['active'])

{{-- Sélecteur de mode partagé entre /producteurs (négociation) et /marketplace (commande
     directe) : deux vraies pages/routes distinctes, mais un même cadre visuel donnant
     l'impression d'une seule expérience à deux modes, façon Alibaba. --}}
<div class="mode-tabs" role="tablist" aria-label="Mode">
  <a href="{{ route('producers.index') }}" role="tab" aria-selected="{{ $active === 'negotiate' ? 'true' : 'false' }}" class="{{ $active === 'negotiate' ? 'active' : '' }}">Négocier avec un producteur</a>
  <a href="{{ route('marketplace.index') }}" role="tab" aria-selected="{{ $active === 'order' ? 'true' : 'false' }}" class="{{ $active === 'order' ? 'active' : '' }}">Commander directement</a>
</div>

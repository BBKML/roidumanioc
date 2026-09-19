@props(['show' => false, 'title' => '', 'close' => "\$set('showForm', false)", 'wide' => false])
<div class="modal-back {{ $show ? 'open' : '' }}" @if($show) wire:key="modal-open" @endif>
  <div class="modal{{ $wide ? ' modal-wide' : '' }}">
    <div class="modal-head">
      <h3>{{ $title }}</h3>
      <button type="button" wire:click="{{ $close }}" aria-label="Fermer">&times;</button>
    </div>
    <div class="modal-body">
      {{ $slot }}
    </div>
    @isset($footer)
      <div class="modal-foot">{{ $footer }}</div>
    @endisset
  </div>
</div>

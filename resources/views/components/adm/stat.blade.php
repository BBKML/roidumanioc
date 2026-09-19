@props(['label', 'value', 'hint' => null, 'tone' => 'flat'])
<div class="card stat-card">
  <div class="stat">
    <span class="k">{{ $label }}</span>
    <span class="v">{{ $value }}</span>
    <span class="d {{ $tone }}">{{ $hint }}</span>
  </div>
  <span class="ic-badge">
    <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{{ $slot }}</svg>
  </span>
</div>

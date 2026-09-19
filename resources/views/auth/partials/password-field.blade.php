@props([
    'name' => 'password',
    'label' => null,
    'autocomplete' => 'current-password',
    'placeholder' => '',
    'required' => true,
    'minlength' => null,
    'hint' => null,
])
@php $label ??= __('guest.password_field_label'); @endphp
<div class="field">
  <label for="{{ $name }}">{{ $label }}</label>
  <div class="pw-wrap">
    <input id="{{ $name }}" type="password" name="{{ $name }}" autocomplete="{{ $autocomplete }}"
           @if($required) required @endif @if($minlength) minlength="{{ $minlength }}" @endif
           placeholder="{{ $placeholder }}">
    <button type="button" class="pw-eye" data-pw-toggle aria-label="{{ __('guest.show_password') }}">
      <svg class="i-show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
      <svg class="i-hide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3l18 18"/><path d="M10.6 10.6a3 3 0 0 0 4.2 4.2"/><path d="M9.9 4.24A9.1 9.1 0 0 1 12 4c6 0 10 8 10 8a17.6 17.6 0 0 1-3.06 3.86M6.1 6.1A17.5 17.5 0 0 0 2 12s4 8 10 8a9 9 0 0 0 4-.94"/></svg>
    </button>
  </div>
  @if ($hint) <span class="hint">{{ $hint }}</span> @endif
  @error($name) <span class="err">{{ $message }}</span> @enderror
</div>

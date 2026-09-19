<x-guest-layout :title="__('guest.forgot_password.title')" :brand-title="__('guest.forgot_password.brand_title')">

  <h1>{{ __('guest.forgot_password.heading') }}</h1>
  <p class="sub">{{ __('guest.forgot_password.subtitle') }}</p>
  {{-- Beaucoup de comptes n'ont qu'un numéro de téléphone (§ Comptes, CLAUDE.md) : cette
       page ne peut rien pour eux, autant le dire plutôt que les laisser deviner. --}}
  <p class="hint">{{ __('guest.forgot_password.phone_only_notice') }} <a href="{{ route('contact') }}">{{ __('guest.forgot_password.phone_only_link') }}</a>.</p>

  @if (session('status'))
    <div class="auth-ok">{{ session('status') }}</div>
  @endif
  @error('email') <div class="auth-err">{{ $message }}</div> @enderror

  <form method="POST" action="{{ route('password.email') }}">
    @csrf
    <div class="field">
      <label for="email">{{ __('guest.forgot_password.email') }}</label>
      <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="vous@exemple.ci">
    </div>
    <button type="submit" class="btn">{{ __('guest.forgot_password.submit') }}</button>
  </form>

  <p class="auth-switch"><a href="{{ route('login') }}">{{ __('guest.forgot_password.back_to_login') }}</a></p>

</x-guest-layout>

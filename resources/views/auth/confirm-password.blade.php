<x-guest-layout :title="__('guest.confirm_password.title')" :brand-title="__('guest.confirm_password.brand_title')">

  <h1>{{ __('guest.confirm_password.heading') }}</h1>
  <p class="sub">{{ __('guest.confirm_password.intro') }}</p>

  @error('password') <div class="auth-err">{{ $message }}</div> @enderror

  <form method="POST" action="{{ route('password.confirm') }}">
    @csrf
    @include('auth.partials.password-field', ['name' => 'password', 'autocomplete' => 'current-password'])
    <button type="submit" class="btn">{{ __('guest.confirm_password.submit') }}</button>
  </form>

</x-guest-layout>

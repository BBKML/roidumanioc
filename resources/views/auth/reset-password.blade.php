<x-guest-layout :title="__('guest.reset_password.title')" :brand-title="__('guest.reset_password.brand_title')">

  <h1>{{ __('guest.reset_password.heading') }}</h1>
  <p class="sub">{{ __('guest.reset_password.subtitle') }}</p>

  @if ($errors->any())
    <div class="auth-err">
      <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
  @endif

  <form method="POST" action="{{ route('password.store') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $request->route('token') }}">

    <div class="field">
      <label for="email">{{ __('guest.reset_password.email') }}</label>
      <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username">
    </div>

    @include('auth.partials.password-field', ['name' => 'password', 'label' => __('guest.reset_password.new_password'), 'autocomplete' => 'new-password', 'placeholder' => __('guest.register.password_placeholder'), 'minlength' => 8])
    @include('auth.partials.password-field', ['name' => 'password_confirmation', 'label' => __('guest.reset_password.confirm'), 'autocomplete' => 'new-password'])

    <button type="submit" class="btn">{{ __('guest.reset_password.submit') }}</button>
  </form>

</x-guest-layout>

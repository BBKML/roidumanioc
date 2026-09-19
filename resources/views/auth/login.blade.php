<x-guest-layout :title="__('guest.login.title')" :brand-title="__('guest.login.brand_title')">

  <h1>{{ __('guest.login.heading') }}</h1>
  <p class="sub">{{ __('guest.login.subtitle') }}</p>

  @if (session('status'))
    <div class="auth-ok">{{ session('status') }}</div>
  @endif

  @if ($errors->any())
    <div class="auth-err">
      @if ($errors->count() === 1)
        {{ $errors->first() }}
      @else
        <b>{{ __('guest.check_form') }}</b>
        <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
      @endif
    </div>
  @endif

  @include('auth.partials.google')

  <form method="POST" action="{{ route('login') }}">
    @csrf

    <div class="field">
      <label for="login">{{ __('guest.login.identifier') }}</label>
      <input id="login" type="text" name="login" value="{{ old('login') }}" required autofocus autocomplete="username" placeholder="vous@exemple.ci ou 07 00 00 00 00">
    </div>

    @include('auth.partials.password-field', ['name' => 'password', 'placeholder' => __('guest.login.password_placeholder')])

    <div class="auth-row">
      <label><input type="checkbox" name="remember"> {{ __('guest.login.remember_me') }}</label>
      @if (Route::has('password.request'))
        <a href="{{ route('password.request') }}">{{ __('guest.login.forgot_password') }}</a>
      @endif
    </div>

    <button type="submit" class="btn">{{ __('guest.login.submit') }}</button>
  </form>

  @if (Route::has('register'))
    <p class="auth-switch">{{ __('guest.login.no_account') }} <a href="{{ route('register') }}">{{ __('guest.login.create_account') }}</a></p>
  @endif

</x-guest-layout>

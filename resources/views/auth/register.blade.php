<x-guest-layout :title="__('guest.register.title')" :brand-title="__('guest.register.brand_title')">

  <h1>{{ __('guest.register.heading') }}</h1>
  <p class="sub">{{ __('guest.register.subtitle') }}</p>

  @if ($errors->any())
    <div class="auth-err">
      <b>{{ __('guest.check_form') }}</b>
      <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
  @endif

  @include('auth.partials.google')

  <form method="POST" action="{{ route('register') }}">
    @csrf

    <div class="field">
      <label for="name">{{ __('guest.register.full_name') }}</label>
      <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" placeholder="Ex : Aïcha Coulibaly">
    </div>

    <p class="hint">{{ __('guest.register.identifier_hint') }}</p>

    <div class="field-row">
      <div class="field">
        <label for="phone">{{ __('guest.register.phone') }}</label>
        <input id="phone" type="text" name="phone" value="{{ old('phone') }}" autocomplete="tel" inputmode="tel" placeholder="07 00 00 00 00">
      </div>
      <div class="field">
        <label for="email">{{ __('guest.register.email') }} <span class="hint" style="font-weight:600">{{ __('guest.register.optional') }}</span></label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="username" placeholder="vous@exemple.ci">
      </div>
    </div>

    <div class="field">
      <label for="city">{{ __('guest.register.city') }} <span class="hint" style="font-weight:600">{{ __('guest.register.optional') }}</span></label>
      <input id="city" type="text" name="city" value="{{ old('city') }}" placeholder="Ex : Daloa">
    </div>

    <div class="field">
      <label>{{ __('guest.register.account_type_label') }}</label>
      @php $accountType = old('account_type', []); @endphp
      <label class="chk"><input type="checkbox" name="account_type[]" value="producteur" {{ in_array('producteur', $accountType, true) ? 'checked' : '' }}> {{ __('guest.register.account_type_producer') }}</label>
      <label class="chk"><input type="checkbox" name="account_type[]" value="acheteur" {{ in_array('acheteur', $accountType, true) ? 'checked' : '' }}> {{ __('guest.register.account_type_buyer') }}</label>
      <p class="hint">{{ __('guest.register.account_type_hint') }}</p>
    </div>

    @include('auth.partials.password-field', ['name' => 'password', 'autocomplete' => 'new-password', 'placeholder' => __('guest.register.password_placeholder'), 'minlength' => 8])
    @include('auth.partials.password-field', ['name' => 'password_confirmation', 'label' => __('guest.register.confirm_password'), 'autocomplete' => 'new-password', 'placeholder' => __('guest.register.confirm_password_placeholder')])

    <button type="submit" class="btn">{{ __('guest.register.submit') }}</button>
  </form>

  <p class="auth-switch">{{ __('guest.register.already_registered') }} <a href="{{ route('login') }}">{{ __('guest.register.login_link') }}</a></p>

</x-guest-layout>

<x-guest-layout :title="__('guest.verify_email.title')" :brand-title="__('guest.verify_email.brand_title')">

  <h1>{{ __('guest.verify_email.heading') }}</h1>
  <p class="sub">{{ __('guest.verify_email.intro') }}</p>

  @if (session('status') === 'verification-link-sent')
    <div class="auth-ok">{{ __('guest.verify_email.link_sent') }}</div>
  @endif

  <form method="POST" action="{{ route('verification.send') }}">
    @csrf
    <button type="submit" class="btn">{{ __('guest.verify_email.resend') }}</button>
  </form>

  <form method="POST" action="{{ route('logout') }}" style="margin-top:1rem">
    @csrf
    <button type="submit" style="background:none;border:none;color:var(--leaf);font-weight:800;cursor:pointer;font-size:.86rem">{{ __('guest.verify_email.logout') }}</button>
  </form>

</x-guest-layout>

{{-- Formulaire de contact — vitrine (Phase 2, refondu en 2 colonnes + carte). --}}
<x-public-layout :title="__('site.contact.title')" :description="__('site.contact.description')">

@php
    $pied = $content['pied'] ?? [];
    $lat = is_numeric($pied['map_lat'] ?? null) ? (float) $pied['map_lat'] : null;
    $lng = is_numeric($pied['map_lng'] ?? null) ? (float) $pied['map_lng'] : null;
    $zoom = (int) ($pied['map_zoom'] ?? 14) ?: 14;
    $hasMap = $lat !== null && $lng !== null;

    $waRaw = trim($pied['whatsapp'] ?? '');
    $waLink = $waRaw === ''
        ? null
        : (str_starts_with($waRaw, 'http') ? $waRaw : 'https://wa.me/'.preg_replace('/\D+/', '', $waRaw));
@endphp

<main id="main">
<span id="top"></span>

<section class="contact-hero">
  <div class="wrap">
    <div class="section-head reveal" style="margin-bottom:2.6rem">
      <p class="eyebrow">{{ __('site.contact.eyebrow') }}</p>
      <h1>{{ __('site.contact.heading') }}</h1>
      <p class="lead">{!! __('site.contact.lead') !!}</p>
    </div>

    <div class="contact-grid">
      <div class="reveal">
        @if (session('contact_sent'))
          <div class="alert-ok" role="status">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
            <span>{{ session('contact_sent') }}</span>
          </div>
        @endif

        <form method="POST" action="{{ route('contact.send') }}" class="contact-form">
          @csrf

          <div class="hp" aria-hidden="true">
            <label>{{ __('site.contact.honeypot_label') }}<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
          </div>

          <div class="row">
            <div class="field">
              <label for="name">{{ __('site.contact.full_name') }}</label>
              <input type="text" id="name" name="name" value="{{ old('name') }}" required maxlength="120">
              @error('name')<span class="err">{{ $message }}</span>@enderror
            </div>
            <div class="field">
              <label for="email">{{ __('site.contact.email') }}</label>
              <input type="email" id="email" name="email" value="{{ old('email') }}" required maxlength="180">
              @error('email')<span class="err">{{ $message }}</span>@enderror
            </div>
          </div>

          <div class="row">
            <div class="field">
              <label for="phone">{{ __('site.contact.phone') }}</label>
              <input type="text" id="phone" name="phone" value="{{ old('phone') }}" maxlength="40">
              @error('phone')<span class="err">{{ $message }}</span>@enderror
            </div>
            <div class="field">
              <label for="subject">{{ __('site.contact.subject') }}</label>
              <input type="text" id="subject" name="subject" value="{{ old('subject') }}" maxlength="160">
              @error('subject')<span class="err">{{ $message }}</span>@enderror
            </div>
          </div>

          <div class="field">
            <label for="message">{{ __('site.contact.message') }}</label>
            <textarea id="message" name="message" required minlength="10" maxlength="4000">{{ old('message') }}</textarea>
            @error('message')<span class="err">{{ $message }}</span>@enderror
          </div>

          <div class="hero-actions">
            <button type="submit" class="btn gold">{{ __('site.contact.submit') }}</button>
            <a href="{{ route('home') }}" class="btn ghost">{{ __('site.contact.back_home') }}</a>
          </div>
        </form>
      </div>

      <aside class="contact-panel reveal">
        <h2>{{ __('site.contact.coords_heading') }}</h2>

        @if ($hasMap)
          <div class="contact-map" id="contactMap"
               data-lat="{{ $lat }}" data-lng="{{ $lng }}" data-zoom="{{ $zoom }}"
               data-label="{{ $pied['city'] ?? 'Le Roi du Manioc' }}"
               role="img" aria-label="{{ __('site.contact.map_alt') }} — {{ $pied['city'] ?? '' }}"></div>
        @endif

        <div class="contact-coords">
          @if (!empty($pied['city']))
            <div class="contact-coord-item">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-7.4 8-13a8 8 0 1 0-16 0c0 5.6 8 13 8 13z"/><circle cx="12" cy="9" r="3"/></svg>
              <div><b>{{ __('site.contact.address_label') }}</b><span>{{ $pied['city'] }}</span></div>
            </div>
          @endif
          @if (!empty($pied['email']))
            <div class="contact-coord-item">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
              <div><b>{{ __('site.contact.email_label') }}</b><a href="mailto:{{ $pied['email'] }}">{{ $pied['email'] }}</a></div>
            </div>
          @endif
          @if ($waLink)
            <div class="contact-coord-item">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326z"/></svg>
              <div><b>{{ __('site.contact.whatsapp_label') }}</b><a href="{{ $waLink }}" target="_blank" rel="noopener">{{ __('site.contact.whatsapp_cta') }}</a></div>
            </div>
          @endif
          @if (!empty($pied['hours']))
            <div class="contact-coord-item">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>
              <div><b>{{ __('site.contact.hours_label') }}</b><span>{{ $pied['hours'] }}</span></div>
            </div>
          @endif
        </div>
      </aside>
    </div>
  </div>
</section>

</main>

</x-public-layout>

{{--
  Page d'inscription publique — partagée en lien direct (bio TikTok/Facebook, etc).
  Page AUTONOME (pas de x-public-layout) : volontairement dépourvue de l'en-tête/menu/pied
  du site pour se comporter comme un Google Form — rien pour distraire le visiteur de
  l'inscription, tout se passe dans cette page.
--}}
@php
    $preview = $preview ?? false;
    $motivations = \App\Http\Controllers\RegistrationFormController::MOTIVATIONS;
    $howHeard = \App\Http\Controllers\RegistrationFormController::HOW_HEARD;
    $paymentMethods = \App\Http\Controllers\RegistrationFormController::PAYMENT_METHODS;
    $experienceLevels = \App\Http\Controllers\RegistrationFormController::EXPERIENCE_LEVELS;
    $statusFunctions = \App\Http\Controllers\RegistrationFormController::STATUS_FUNCTIONS;
    $ageRanges = \App\Http\Controllers\RegistrationFormController::AGE_RANGES;
@endphp
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $form->title }}</title>
@if ($form->subtitle)<meta name="description" content="{{ $form->subtitle }}">@endif
<meta name="robots" content="noindex">
<link rel="icon" href="{{ asset('img/logo.png') }}">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap">
<style>
:root{
  --leaf:#2f7a41; --leaf-600:#3b9a4f; --gold:#c6952f; --clay:#a9502c;
  --ink:#1b2a1d; --ink-soft:#586457; --sand:#ffffff; --paper:#ffffff;
}
*,*::before,*::after{box-sizing:border-box;}
html{scroll-behavior:smooth;}
@media (prefers-reduced-motion:reduce){html{scroll-behavior:auto;}}
body{margin:0;background:#e9e7df;color:var(--ink);font-family:Manrope,system-ui,sans-serif;line-height:1.55;}
.gform-page{min-height:100vh;padding:2.5rem 1rem 4rem;}
.gform-column{max-width:680px;margin:0 auto;}
.gform-card{background:var(--paper);border-radius:14px;box-shadow:0 1px 2px rgba(27,42,29,.06),0 2px 8px rgba(27,42,29,.07);overflow:hidden;margin:0 0 1rem;}
.gform-header{padding:0;}
.gform-topbar{height:10px;background:linear-gradient(90deg,var(--leaf),var(--gold));}
.gform-header-body{padding:1.6rem 1.7rem 1.4rem;}
.gform-affiche{padding:0;line-height:0;}
.gform-affiche img{display:block;width:100%;height:auto;}
.gform-header-body h1{font-size:1.65rem;font-weight:800;line-height:1.25;margin:0 0 .6rem;color:var(--ink);}
.gform-subtitle{display:inline-block;margin:0 0 1rem;color:#fff;background:var(--clay);font-weight:800;font-size:.78rem;letter-spacing:.03em;text-transform:uppercase;padding:.35rem .8rem;border-radius:999px;}
.gform-intro{margin:0 0 .9rem;color:var(--ink);font-size:.94rem;line-height:1.55;white-space:pre-line;}
.gform-required-note{margin:0;font-size:.78rem;color:var(--clay);}
.gform-card.gform-info{padding:1.5rem 1.7rem;}
.gform-info h2{display:flex;align-items:center;gap:.65rem;font-size:1.05rem;font-weight:800;letter-spacing:.02em;text-transform:uppercase;margin:0 0 1rem;color:var(--ink);}
.gform-num{display:inline-flex;align-items:center;justify-content:center;width:1.6rem;height:1.6rem;border-radius:50%;background:var(--leaf);color:#fff;font-size:.8rem;font-weight:800;flex:none;}
.gform-info ul{margin:0 0 .9rem;padding-left:1.3rem;display:grid;gap:.55rem;color:var(--ink);font-size:.92rem;line-height:1.5;}
.gform-info ul:last-child{margin-bottom:0;}
.gform-info ul li::marker{color:var(--leaf);font-weight:800;}
.gform-info li strong,.gform-intro strong,.gform-info p strong{color:var(--ink);font-weight:800;}
.gform-subheading{font-weight:800;text-decoration:underline;text-decoration-color:var(--gold);text-underline-offset:3px;margin:.2rem 0 .6rem;font-size:.95rem;color:var(--ink);}
.gform-info p{margin:0 0 .6rem;font-size:.92rem;line-height:1.5;color:var(--ink);white-space:pre-line;}
.gform-price{font-size:1.5rem;font-weight:800;color:var(--leaf);margin:0 0 .6rem;}
.gform-sub-lead{font-weight:800;margin:1rem 0 .5rem;font-size:.92rem;color:var(--ink);}
.gform-countdown{margin:0 0 1.1rem;padding:1.1rem 1.1rem 1.3rem;border-radius:14px;border:1px solid rgba(198,149,47,.4);background:linear-gradient(135deg,rgba(198,149,47,.12),rgba(169,80,44,.07));display:flex;flex-direction:column;align-items:center;gap:.9rem;}
.gform-countdown-label{margin:0;font-weight:800;font-size:.8rem;letter-spacing:.03em;text-transform:uppercase;color:var(--clay);}
.gform-rings{display:flex;gap:.9rem;justify-content:center;flex-wrap:wrap;}
.gform-ring{display:flex;flex-direction:column;align-items:center;gap:.35rem;}
.gform-ring-dial{position:relative;width:66px;height:66px;}
.gform-ring-dial svg{position:absolute;inset:0;width:100%;height:100%;transform:rotate(-90deg);}
.gform-ring-dial circle{fill:none;stroke-width:7;}
.gform-ring-track{stroke:rgba(27,42,29,.08);}
.gform-ring-progress{stroke:var(--leaf);stroke-linecap:round;transition:stroke-dashoffset .9s linear;}
.gform-ring-value{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:1.15rem;color:var(--ink);font-variant-numeric:tabular-nums;}
.gform-ring-label{font-size:.62rem;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:var(--ink-soft);}
.gform-countdown-ended{margin:0;font-weight:800;font-size:.9rem;color:var(--ink-soft);text-align:center;}
.gform-offers{display:grid;grid-template-columns:1fr 1fr;gap:.9rem;margin:0 0 1rem;}
@media(max-width:560px){.gform-offers{grid-template-columns:1fr;}}
.gform-offer{border:1px solid var(--sand);border-radius:12px;padding:1.1rem 1.05rem;display:flex;flex-direction:column;gap:.35rem;background:#fbfaf7;}
.gform-offer-deposit{border-color:rgba(198,149,47,.5);background:linear-gradient(180deg,#fffaf0,#fbfaf7);}
.gform-offer-tag{margin:0;font-weight:800;font-size:.76rem;text-transform:uppercase;letter-spacing:.03em;color:var(--ink-soft);}
.gform-offer-deposit .gform-offer-tag{color:var(--clay);}
.gform-offer-amount{margin:0;font-size:1.3rem;font-weight:800;color:var(--ink);line-height:1.2;}
.gform-offer-note{margin:0;font-size:.85rem;color:var(--ink-soft);line-height:1.5;}
.gform-question{padding:1.3rem 1.7rem;}
.gform-question label.q{display:block;font-weight:700;font-size:.94rem;margin-bottom:.8rem;color:var(--ink);}
.req{color:var(--clay);margin-left:.15rem;}
.gform-input{width:100%;border:none;border-bottom:1px solid var(--sand);background:transparent;padding:.45rem .1rem;font:inherit;font-size:.95rem;color:var(--ink);outline:none;}
.gform-input:focus{border-bottom:2px solid var(--leaf);padding-bottom:calc(.45rem - 1px);}
select.gform-input{appearance:auto;background:var(--paper);}
textarea.gform-input{border:1px solid var(--sand);border-radius:8px;padding:.65rem .8rem;min-height:90px;resize:vertical;}
.gform-hint{margin:.5rem 0 0;font-size:.8rem;color:var(--ink-soft);}
.gform-err{display:block;margin-top:.5rem;font-size:.82rem;color:var(--clay);}
.gform-options{display:grid;gap:.7rem;margin-top:.2rem;}
.gform-option{display:flex;align-items:center;gap:.65rem;font-size:.93rem;color:var(--ink);cursor:pointer;}
.gform-option input[type=checkbox],.gform-option input[type=radio]{width:19px;height:19px;accent-color:var(--leaf);flex:none;cursor:pointer;}
.gform-other-input{flex:1;border:none;border-bottom:1px solid var(--sand);background:transparent;padding:.3rem .1rem;font:inherit;font-size:.9rem;color:var(--ink);outline:none;min-width:0;}
.gform-other-input:focus{border-bottom:2px solid var(--leaf);}
.gform-cta{padding:1.6rem 1.7rem;text-align:center;}
.gform-cta-lead{font-weight:800;font-size:1.02rem;margin:0 0 1rem;}
.gform-cta-btn{display:inline-block;background:linear-gradient(180deg,var(--leaf-600),var(--leaf));color:#fff;font-weight:800;font-size:1.1rem;font-style:italic;text-decoration:none;padding:.85rem 2.6rem;border-radius:10px;box-shadow:0 4px 0 rgba(27,42,29,.25);}
.gform-cta-lead2{padding:1.1rem 1.7rem;text-align:center;font-weight:800;font-size:.96rem;}
.hp{position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;}
.gform-submit{padding:1.4rem 1.7rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;}
.gform-submit-btn{appearance:none;border:none;border-radius:999px;background:var(--leaf);color:#fff;font:inherit;font-weight:700;font-size:.95rem;padding:.75rem 1.9rem;cursor:pointer;transition:background .15s ease;}
.gform-submit-btn:hover{background:var(--leaf-600);}
.gform-whatsapp-link{display:inline-flex;align-items:center;gap:.5rem;background:#25D366;color:#fff;font-weight:700;font-size:.85rem;text-decoration:none;padding:.55rem 1.1rem;border-radius:999px;transition:background .15s ease;}
.gform-whatsapp-link svg{width:17px;height:17px;flex:none;}
.gform-whatsapp-link:hover{background:#1fb959;}
.gform-success{padding:2rem 1.7rem;text-align:center;}
.gform-success h2{margin:0 0 .5rem;color:var(--leaf);}
.gform-success p{margin:0;color:var(--ink-soft);}
.gform-footer{text-align:center;padding:1rem 1rem 0;color:var(--ink-soft);font-size:.82rem;}
.gform-footer a{color:var(--leaf);font-weight:700;text-decoration:none;}
.gform-brand{margin:.6rem 0 0;font-weight:700;color:var(--ink);}
.gform-preview-ribbon{position:sticky;top:0;z-index:9;background:var(--clay);color:#fff;text-align:center;font-weight:800;font-size:.78rem;letter-spacing:.03em;text-transform:uppercase;padding:.5rem;}
@media(max-width:520px){.gform-header-body,.gform-info,.gform-question,.gform-submit,.gform-success{padding-left:1.1rem;padding-right:1.1rem;}}
</style>
</head>
<body>
@if ($preview)
  <div class="gform-preview-ribbon">Aperçu — ceci n'envoie rien</div>
@endif
<div class="gform-page">
  <div class="gform-column">

    <div class="gform-card gform-header">
      <div class="gform-topbar"></div>
      <div class="gform-header-body">
        <h1>{{ $form->title }}</h1>
        @if ($form->subtitle)<p class="gform-subtitle">{{ $form->subtitle }}</p>@endif
        @if ($form->intro)<p class="gform-intro">{!! $form->renderText('intro') !!}</p>@endif
        <p class="gform-required-note">* Indique une question obligatoire</p>
      </div>
    </div>

    @if ($form->coverImageUrl())
      @php
        $afficheDims = $form->coverImageDimensions();
        $afficheRatio = $afficheDims ? $afficheDims['width'].'/'.$afficheDims['height'] : null;
      @endphp
      <div class="gform-card gform-affiche">
        <img
          src="{{ $form->coverImageUrl() }}"
          alt="{{ $form->title }}"
          @if ($afficheDims)
            width="{{ $afficheDims['width'] }}"
            height="{{ $afficheDims['height'] }}"
            style="aspect-ratio:{{ $afficheRatio }}"
          @endif
        >
      </div>
    @endif

    @if ($form->lines('objectives'))
      <div class="gform-card gform-info">
        <h2><span class="gform-num">1</span> Pourquoi cette formation</h2>
        {!! $form->renderLines('objectives') !!}
      </div>
    @endif

    @if ($form->lines('schedule_info'))
      <div class="gform-card gform-info">
        <h2><span class="gform-num">2</span> Le format</h2>
        {!! $form->renderLines('schedule_info') !!}
      </div>
    @endif

    @if ($form->lines('program') || $form->lines('certifications'))
      <div class="gform-card gform-info">
        <h2><span class="gform-num">3</span> Programme</h2>
        {!! $form->renderLines('program') !!}
        @if ($form->lines('certifications'))
          <p class="gform-sub-lead">Cette formation est faite pour vous si :</p>
          {!! $form->renderLines('certifications') !!}
        @endif
      </div>
    @endif

    @if ($form->price_amount || $form->lines('price_note') || $form->lines('payment_methods') || $form->early_bird_deadline || $form->deposit_amount)
      <div class="gform-card gform-info">
        <h2><span class="gform-num">4</span> Votre investissement</h2>

        @if ($form->early_bird_deadline)
          <div class="gform-countdown" data-deadline="{{ $form->early_bird_deadline->toIso8601String() }}">
            <p class="gform-countdown-label">🎁 Offre premiers inscrits</p>
            <div class="gform-rings" data-countdown-rings>
              @foreach ([['days', 'Jours'], ['hours', 'Heures'], ['minutes', 'Min'], ['seconds', 'Sec']] as [$unit, $label])
                <div class="gform-ring">
                  <div class="gform-ring-dial">
                    <svg viewBox="0 0 66 66">
                      <circle class="gform-ring-track" cx="33" cy="33" r="29"></circle>
                      <circle class="gform-ring-progress" data-ring="{{ $unit }}" cx="33" cy="33" r="29"></circle>
                    </svg>
                    <span class="gform-ring-value" data-value="{{ $unit }}">00</span>
                  </div>
                  <span class="gform-ring-label">{{ $label }}</span>
                </div>
              @endforeach
            </div>
            <p class="gform-countdown-ended" data-countdown-ended hidden>⏳ L'offre premiers inscrits est terminée — les places restent ouvertes, hors bonus.</p>
          </div>
        @endif

        @if ($form->price_amount || $form->deposit_amount)
          <div class="gform-offers">
            @if ($form->price_amount)
              <div class="gform-offer">
                <p class="gform-offer-tag">Paiement intégral</p>
                <p class="gform-offer-amount">{{ $form->price_amount }}</p>
              </div>
            @endif
            @if ($form->deposit_amount)
              <div class="gform-offer gform-offer-deposit">
                <p class="gform-offer-tag">🔐 Réservez votre place</p>
                <p class="gform-offer-amount">à partir de {{ $form->deposit_amount }}</p>
                @if ($form->deposit_note)
                  <p class="gform-offer-note">{!! $form->renderText('deposit_note') !!}</p>
                @endif
              </div>
            @endif
          </div>
        @endif

        {!! $form->renderLines('price_note') !!}

        @if ($form->lines('payment_methods'))
          <p class="gform-sub-lead">Moyens de paiement disponibles :</p>
          {!! $form->renderLines('payment_methods') !!}
        @endif
      </div>
    @endif

    @unless (session('registration_sent'))
      <div class="gform-card gform-cta">
        <p class="gform-cta-lead">Prêt·e à structurer une activité manioc rentable ?</p>
        <a href="#formulaire" class="gform-cta-btn">Je réserve ma place</a>
      </div>

      <div class="gform-card gform-cta-lead2" id="formulaire">
        <p>Complétez le formulaire ci-dessous pour votre pré-inscription :</p>
      </div>
    @endunless

    @if (session('registration_sent'))
      <div class="gform-card gform-success">
        <h2>Pré-inscription bien reçue</h2>
        <p>{{ session('registration_sent') }}</p>
      </div>
    @else
      <form method="POST" action="{{ route('inscription.store', $form) }}" @if ($preview) onsubmit="return false" @endif>
        @csrf

        <div class="hp" aria-hidden="true">
          <label>Ne pas remplir<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
        </div>

        <div class="gform-card gform-question">
          <label class="q" for="email">Adresse e-mail(facultatif)<span class=""></span></label>
          <input class="gform-input" type="email" id="email" name="email" value="{{ old('email') }}" maxlength="180">
          @error('email')<span class="gform-err">{{ $message }}</span>@enderror
        </div>

        <div class="gform-card gform-question">
          <label class="q" for="gender">Genre<span class="req">*</span></label>
          <select class="gform-input" id="gender" name="gender" required>
            <option value="">Sélectionner</option>
            <option value="Homme" @selected(old('gender') === 'Homme')>Homme</option>
            <option value="Femme" @selected(old('gender') === 'Femme')>Femme</option>
          </select>
          @error('gender')<span class="gform-err">{{ $message }}</span>@enderror
        </div>

        <div class="gform-card gform-question">
          <label class="q" for="last_name">Nom<span class="req">*</span></label>
          <input class="gform-input" type="text" id="last_name" name="last_name" value="{{ old('last_name') }}" required maxlength="100">
          @error('last_name')<span class="gform-err">{{ $message }}</span>@enderror
        </div>

        <div class="gform-card gform-question">
          <label class="q" for="first_name">Prénoms<span class="req">*</span></label>
          <input class="gform-input" type="text" id="first_name" name="first_name" value="{{ old('first_name') }}" required maxlength="100">
          @error('first_name')<span class="gform-err">{{ $message }}</span>@enderror
        </div>

        <div class="gform-card gform-question">
          <label class="q" for="phone_1">N° de téléphone 1 (avec indicatif du pays)<span class="req">*</span></label>
          <input class="gform-input" type="text" id="phone_1" name="phone_1" value="{{ old('phone_1') }}" required maxlength="40">
          @error('phone_1')<span class="gform-err">{{ $message }}</span>@enderror
        </div>

        <div class="gform-card gform-question">
          <label class="q" for="phone_2">N° de téléphone 2 (facultatif)</label>
          <input class="gform-input" type="text" id="phone_2" name="phone_2" value="{{ old('phone_2') }}" maxlength="40">
        </div>

        <div class="gform-card gform-question">
          <label class="q" for="whatsapp">Numéro WhatsApp<span class="req">*</span></label>
          <input class="gform-input" type="text" id="whatsapp" name="whatsapp" value="{{ old('whatsapp') }}" required maxlength="40">
          @error('whatsapp')<span class="gform-err">{{ $message }}</span>@enderror
        </div>

        <div class="gform-card gform-question">
          <label class="q">Niveau d'expérience dans la filière manioc<span class="req">*</span></label>
          <div class="gform-options">
            @foreach ($experienceLevels as $option)
              <label class="gform-option">
                <input type="radio" name="experience_level" value="{{ $option }}" required @checked(old('experience_level') === $option)>
                {{ $option }}
              </label>
            @endforeach
          </div>
          @error('experience_level')<span class="gform-err">{{ $message }}</span>@enderror
        </div>

        <div class="gform-card gform-question">
          <label class="q">Statut / Fonction<span class="req">*</span></label>
          <div class="gform-options">
            @foreach ($statusFunctions as $option)
              @if ($option === 'Autre')
                <label class="gform-option">
                  <input type="radio" name="profession" value="Autre" required @checked(old('profession') === 'Autre')>
                  Autre :
                  <input type="text" name="profession_other" value="{{ old('profession_other') }}" maxlength="160" class="gform-other-input">
                </label>
              @else
                <label class="gform-option">
                  <input type="radio" name="profession" value="{{ $option }}" required @checked(old('profession') === $option)>
                  {{ $option }}
                </label>
              @endif
            @endforeach
          </div>
          @error('profession')<span class="gform-err">{{ $message }}</span>@enderror
          @error('profession_other')<span class="gform-err">{{ $message }}</span>@enderror
        </div>

        <div class="gform-card gform-question">
          <label class="q">Tranche d'âge<span class="req">*</span></label>
          <div class="gform-options">
            @foreach ($ageRanges as $option)
              <label class="gform-option">
                <input type="radio" name="age_range" value="{{ $option }}" required @checked(old('age_range') === $option)>
                {{ $option }}
              </label>
            @endforeach
          </div>
          @error('age_range')<span class="gform-err">{{ $message }}</span>@enderror
        </div>

        <div class="gform-card gform-question">
          <label class="q" for="company">Entreprise ou organisation (facultatif)</label>
          <input class="gform-input" type="text" id="company" name="company" value="{{ old('company') }}" maxlength="160">
          @error('company')<span class="gform-err">{{ $message }}</span>@enderror
        </div>

        <div class="gform-card gform-question">
          <label class="q" for="city_country">Ville et pays de résidence<span class="req">*</span></label>
          <input class="gform-input" type="text" id="city_country" name="city_country" value="{{ old('city_country') }}" required maxlength="160">
          @error('city_country')<span class="gform-err">{{ $message }}</span>@enderror
        </div>

        <div class="gform-card gform-question">
          <label class="q">Motivation(s) pour suivre cette formation</label>
          <div class="gform-options">
            @foreach ($motivations as $option)
              <label class="gform-option">
                <input type="checkbox" name="motivations[]" value="{{ $option }}" @checked(collect(old('motivations', []))->contains($option))>
                {{ $option }}
              </label>
            @endforeach
            <label class="gform-option">
              <input type="checkbox" name="motivations[]" value="Autre" @checked(!empty(old('motivation_other')))>
              Autre :
              <input type="text" name="motivation_other" value="{{ old('motivation_other') }}" maxlength="160" class="gform-other-input">
            </label>
          </div>
        </div>

        <div class="gform-card gform-question">
          <label class="q" for="expectations">Quelles sont vos attentes pour la formation ? (facultatif)</label>
          <textarea class="gform-input" id="expectations" name="expectations" maxlength="2000">{{ old('expectations') }}</textarea>
        </div>

        <div class="gform-card gform-question">
          <label class="q">Comment avez-vous été informé de cette formation ?</label>
          <div class="gform-options">
            @foreach ($howHeard as $option)
              <label class="gform-option">
                <input type="radio" name="how_heard" value="{{ $option }}" @checked(old('how_heard') === $option)>
                {{ $option }}
              </label>
            @endforeach
          </div>
        </div>

        <div class="gform-card gform-question">
          <label class="q">Je paie ma participation par<span class="req">*</span></label>
          <div class="gform-options">
            @foreach ($paymentMethods as $option)
              <label class="gform-option">
                <input type="radio" name="payment_method" value="{{ $option }}" required @checked(old('payment_method') === $option)>
                {{ $option }}
              </label>
            @endforeach
          </div>
          @error('payment_method')<span class="gform-err">{{ $message }}</span>@enderror
        </div>

        <div class="gform-card gform-question">
          <label class="q" for="payment_frequency">Fréquence de paiement souhaitée (facultatif)</label>
          <input class="gform-input" type="text" id="payment_frequency" name="payment_frequency" value="{{ old('payment_frequency') }}" placeholder="Ex : en 1 fois, en plusieurs tranches…" maxlength="160">
        </div>

        <div class="gform-card gform-submit">
          <button type="submit" class="gform-submit-btn">Envoyer ma pré-inscription</button>
          @if ($form->whatsapp_number)
            <a href="https://wa.me/{{ preg_replace('/\D+/', '', $form->whatsapp_number) }}" target="_blank" rel="noopener" class="gform-whatsapp-link">
              <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326zM7.994 14.521a6.6 6.6 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.56 6.56 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592m3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.73.73 0 0 0-.529.247c-.182.198-.691.677-.691 1.654s.71 1.916.81 2.049c.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232"/></svg>
               WhatsApp
            </a>
          @endif
        </div>
      </form>
    @endif

    <div class="gform-footer">
      <p class="gform-brand">Le Roi du Manioc</p>
    </div>

  </div>
</div>
@if ($form->early_bird_deadline)
<script>
(function () {
  var CIRC = 2 * Math.PI * 29;

  document.querySelectorAll('[data-deadline]').forEach(function (box) {
    var deadline = new Date(box.getAttribute('data-deadline')).getTime();
    var ringsEl = box.querySelector('[data-countdown-rings]');
    var endedEl = box.querySelector('[data-countdown-ended]');

    var rings = {};
    box.querySelectorAll('[data-ring]').forEach(function (circle) {
      circle.style.strokeDasharray = CIRC;
      rings[circle.getAttribute('data-ring')] = circle;
    });
    var values = {};
    box.querySelectorAll('[data-value]').forEach(function (el) {
      values[el.getAttribute('data-value')] = el;
    });

    function pad(n) { return String(n).padStart(2, '0'); }

    function setUnit(unit, value, max) {
      if (values[unit]) values[unit].textContent = pad(value);
      if (rings[unit]) rings[unit].style.strokeDashoffset = CIRC * (1 - Math.min(value, max) / max);
    }

    function tick() {
      var diff = deadline - Date.now();
      if (diff <= 0) {
        clearInterval(interval);
        if (ringsEl) ringsEl.hidden = true;
        if (endedEl) endedEl.hidden = false;
        return;
      }
      var totalSeconds = Math.floor(diff / 1000);
      setUnit('days', Math.floor(totalSeconds / 86400), 30);
      setUnit('hours', Math.floor((totalSeconds % 86400) / 3600), 23);
      setUnit('minutes', Math.floor((totalSeconds % 3600) / 60), 59);
      setUnit('seconds', totalSeconds % 60, 59);
    }

    var interval = setInterval(tick, 1000);
    tick();
  });
})();
</script>
@endif
</body>
</html>

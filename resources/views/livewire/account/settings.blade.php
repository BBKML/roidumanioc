<div style="display:flex;flex-direction:column;gap:1.4rem">

  <div class="page-intro">
    <div>
      <h2>Mon compte</h2>
      <p>Vos informations, votre mot de passe et votre session.</p>
    </div>
    <form method="POST" action="{{ route('logout') }}">
      @csrf
      <button type="submit" class="btn ghost">
        <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
        Se déconnecter
      </button>
    </form>
  </div>

  <div class="grid g-2">
    {{-- Identité --}}
    <form wire:submit="updateProfile" class="card pad-lg">
      <div class="card-head"><h3>Identité</h3></div>
      <div class="field">
        <label>Nom complet</label>
        <input type="text" wire:model="name">
        @error('name') <span class="inline-err">{{ $message }}</span> @enderror
      </div>
      <div class="field-row">
        <div class="field">
          <label>E-mail</label>
          <input type="email" wire:model="email">
          @error('email') <span class="inline-err">{{ $message }}</span> @enderror
        </div>
        <div class="field">
          <label>Téléphone</label>
          <input type="text" wire:model="phone">
          @error('phone') <span class="inline-err">{{ $message }}</span> @enderror
        </div>
      </div>
      <div class="field">
        <label>Ville</label>
        <input type="text" wire:model="city">
        @error('city') <span class="inline-err">{{ $message }}</span> @enderror
      </div>
      @if ($user->provider === 'google')
        <p class="muted" style="font-size:.78rem">Compte lié à Google.</p>
      @endif
      <button type="submit" class="btn">Enregistrer mes informations</button>
    </form>

    {{-- Résumé (apprenant) / rôle (admin) --}}
    <div class="card pad-lg">
      <div class="card-head"><h3>{{ $user->isAdmin() ? 'Rôle & accès' : 'Résumé' }}</h3></div>
      @if ($summary)
        <ul class="activity reset">
          <li><span class="di"><svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4v16l7-2 7 2V4l-7 2-7-2z"/></svg></span>
            <div>{{ $summary['formations'] }} formation(s) suivie(s)<br><time>Membre depuis {{ $user->joined_at?->translatedFormat('F Y') ?? $user->created_at->translatedFormat('F Y') }}</time></div></li>
          <li><span class="di"><svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/><path d="M2 3h3l2.5 13h11L21 7H6"/></svg></span>
            <div>{{ $summary['orders'] }} commande(s) · {{ $summary['payments'] }} paiement(s)</div></li>
        </ul>
      @else
        <p style="font-size:.88rem;color:var(--ink-soft)">
          Vous êtes connecté en tant qu'<b>administrateur</b>. La gestion des rôles des autres membres se fait dans <b>Membres</b>.
        </p>
        <span class="pill info" style="margin-top:.4rem"><span class="dot"></span>Administrateur</span>
      @endif
    </div>
  </div>

  @unless ($user->isAdmin())
    {{-- Espaces producteur / acheteur --}}
    <div class="grid g-2">
      <div class="card pad-lg">
        <div class="card-head"><h3>Profil producteur</h3></div>
        @if ($producerProfile)
          <p style="font-size:.88rem;color:var(--ink-soft)">
            Votre profil producteur est actif — <b>{{ $producerProfile->business_name }}</b>.
          </p>
          <a href="{{ route('learner.producer') }}" class="btn ghost" wire:navigate>Modifier mon profil producteur</a>
        @else
          <p style="font-size:.88rem;color:var(--ink-soft)">
            Vendez vos récoltes, boutures, produits transformés, intrants ou animaux d'élevage sur la marketplace.
          </p>
          <a href="{{ route('learner.producer') }}" class="btn" wire:navigate>Devenir producteur</a>
        @endif
      </div>

      <div class="card pad-lg">
        <div class="card-head"><h3>Profil acheteur</h3></div>
        @if ($buyerProfile)
          <p style="font-size:.88rem;color:var(--ink-soft)">
            Votre profil acheteur est actif ({{ $buyerProfile->buyer_type->label() }}).
          </p>
          <a href="{{ route('learner.buyer') }}" class="btn ghost" wire:navigate>Modifier mon profil acheteur</a>
        @else
          <p style="font-size:.88rem;color:var(--ink-soft)">
            Trouvez des producteurs et passez vos besoins d'achat sur la marketplace.
          </p>
          <a href="{{ route('learner.buyer') }}" class="btn" wire:navigate>Devenir acheteur</a>
        @endif
      </div>
    </div>
  @endunless

  {{-- Sécurité --}}
  <div class="grid g-2">
    <form wire:submit="updatePassword" class="card pad-lg">
      <div class="card-head">
        <h3>
          <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
          Sécurité
        </h3>
      </div>
      @unless ($user->isOAuthOnly())
        <div class="field">
          <label>Mot de passe actuel</label>
          <input type="password" wire:model="current_password" autocomplete="current-password">
          @error('current_password') <span class="inline-err">{{ $message }}</span> @enderror
        </div>
      @else
        <p class="muted" style="font-size:.8rem">Définissez un mot de passe pour aussi pouvoir vous connecter sans Google.</p>
      @endunless
      <div class="field-row">
        <div class="field">
          <label>Nouveau mot de passe</label>
          <input type="password" wire:model="password" autocomplete="new-password">
          @error('password') <span class="inline-err">{{ $message }}</span> @enderror
        </div>
        <div class="field">
          <label>Confirmer</label>
          <input type="password" wire:model="password_confirmation" autocomplete="new-password">
        </div>
      </div>
      <button type="submit" class="btn ghost">Changer le mot de passe</button>
    </form>

    {{-- Zone dangereuse --}}
    <div class="danger-zone">
      <h3>Supprimer mon compte</h3>
      <p style="font-size:.84rem;margin:.4rem 0 .9rem">Action définitive : vos accès aux formations et votre historique seront perdus.</p>
      @if ($user->isLastActiveAdmin())
        <p class="muted" style="font-size:.82rem">Vous êtes le dernier administrateur actif — la suppression est bloquée.</p>
      @else
        <form wire:submit="deleteAccount">
          @unless ($user->isOAuthOnly())
            <div class="field">
              <label>Confirmez avec votre mot de passe</label>
              <input type="password" wire:model="delete_password" autocomplete="current-password">
              @error('delete_password') <span class="inline-err">{{ $message }}</span> @enderror
            </div>
          @endunless
          <button type="submit" class="btn danger" data-confirm="Supprimer définitivement votre compte ?">Supprimer définitivement</button>
        </form>
      @endif
    </div>
  </div>

</div>

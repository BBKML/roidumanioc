<div style="display:flex;flex-direction:column;gap:1.4rem">

  <div class="page-intro">
    <div>
      <h2>Membres &amp; comptes</h2>
      <p>{{ $stats['total'] }} comptes · {{ $stats['active'] }} actifs · {{ $stats['admins'] }} administrateur(s)</p>
    </div>
    <button class="btn" wire:click="newMember">
      <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
      Créer un compte
    </button>
  </div>

  @if ($tempPassword)
    <div class="card pad-lg" style="border-color:var(--leaf)">
      <div class="card-head"><h3>Mot de passe temporaire — {{ $tempPasswordFor }}</h3>
        <button class="link-btn" wire:click="$set('tempPassword', null)">Fermer</button></div>
      <p class="muted" style="font-size:.84rem">Communiquez-le au membre par un canal sûr (WhatsApp, en personne). Il ne sera plus affiché ensuite et le membre devra le changer à la première connexion.</p>
      <p class="pay-panel" style="font-size:1.15rem;font-weight:800;letter-spacing:.03em">{{ $tempPassword }}</p>
    </div>
  @endif

  <div style="display:flex;gap:.8rem;flex-wrap:wrap;align-items:center">
    <label class="search" style="background:var(--paper)">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
      <input type="search" wire:model.live.debounce.300ms="search" placeholder="Nom, e-mail, ville…">
    </label>
    <div class="tabs">
      <button class="{{ $filter === 'tous' ? 'on' : '' }}" wire:click="$set('filter', 'tous')">Tous</button>
      <button class="{{ $filter === 'admin' ? 'on' : '' }}" wire:click="$set('filter', 'admin')">Admins</button>
      <button class="{{ $filter === 'apprenant' ? 'on' : '' }}" wire:click="$set('filter', 'apprenant')">Apprenants</button>
      <button class="{{ $filter === 'suspendu' ? 'on' : '' }}" wire:click="$set('filter', 'suspendu')">Suspendus</button>
    </div>
    <x-adm.loading-note />
  </div>

  <div class="table-wrap stack-mobile">
    <table>
      <thead><tr>
        <x-adm.sortable-th field="name" :sort="$sort" :direction="$direction">Nom</x-adm.sortable-th>
        <th>E-mail</th>
        <th>Rôle</th>
        <x-adm.sortable-th field="city" :sort="$sort" :direction="$direction">Ville</x-adm.sortable-th>
        <x-adm.sortable-th field="validated_count" :sort="$sort" :direction="$direction">Formations</x-adm.sortable-th>
        <th>Statut</th>
        <th></th>
      </tr></thead>
      <tbody>
        @forelse ($members as $member)
          @php $isMe = $member->id === auth()->id(); @endphp
          <tr class="row" wire:key="member-{{ $member->id }}">
            <td class="card-title">
              <div class="cell-main">
                <span class="avatar" style="width:30px;height:30px;font-size:.72rem">{{ $member->initials() }}</span>
                {{ $member->name }}@if ($isMe) <span class="muted">(vous)</span>@endif
                @if ($member->provider === 'google')<span class="badge-prem" title="Connexion Google">G</span>@endif
              </div>
            </td>
            <td class="muted" data-label="E-mail">{{ $member->email ?: $member->phone ?: '—' }}
              @if ($member->delivery_strikes >= 2)
                <br><span class="pill warn" style="font-size:.62rem"><span class="dot"></span>Paiement livraison bloqué</span>
              @endif
            </td>
            <td data-label="Rôle">
              <span class="pill {{ $member->isAdmin() ? 'info' : 'neutral' }}"><span class="dot"></span>{{ $member->role->label() }}</span>
            </td>
            <td class="muted" data-label="Ville">{{ $member->city ?: '—' }}</td>
            <td class="nums" data-label="Formations">{{ $member->validated_count }}</td>
            <td data-label="Statut"><x-adm.pill :status="$member->status" /></td>
            <td class="card-actions">
              <div class="icon-actions">
                <button class="iact" wire:click="changeRole({{ $member->id }})" @disabled($isMe)
                        data-confirm="Changer le rôle de {{ $member->name }} ?" title="Changer le rôle">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/></svg>
                </button>
                <button class="iact" wire:click="resetPassword({{ $member->id }})" @disabled($isMe)
                        data-confirm="Générer un nouveau mot de passe pour {{ $member->name }} ?" title="Réinitialiser le mot de passe">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="8" cy="15" r="4"/><path d="M10.8 12.2 21 2M17 6l3 3M15 8l2 2"/></svg>
                </button>
                @if ($member->delivery_strikes >= 2)
                  <button class="iact primary" wire:click="clearDeliveryStrikes({{ $member->id }})"
                          data-confirm="Réactiver le paiement à la livraison pour {{ $member->name }} ?" title="Réactiver le paiement à la livraison">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/><path d="M2 3h3l2.5 13h11L21 7H6"/></svg>
                  </button>
                @endif
                <button class="iact {{ $member->isActive() ? '' : 'primary' }}" wire:click="toggleSuspend({{ $member->id }})" @disabled($isMe)
                        data-confirm="{{ $member->isActive() ? 'Suspendre le compte de '.$member->name.' ? Il sera immédiatement déconnecté et ne pourra plus se reconnecter.' : 'Réactiver le compte de '.$member->name.' ?' }}"
                        title="{{ $member->isActive() ? 'Suspendre' : 'Réactiver' }}">
                  @if ($member->isActive())
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="6" y="5" width="4" height="14" rx="1"/><rect x="14" y="5" width="4" height="14" rx="1"/></svg>
                  @else
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M20 6 9 17l-5-5"/></svg>
                  @endif
                </button>
                <button class="iact danger" wire:click="deleteMember({{ $member->id }})" @disabled($isMe)
                        data-confirm="Supprimer le compte de {{ $member->name }} ?" title="Supprimer">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M9 7V5h6v2M6 7l1 13h10l1-13"/></svg>
                </button>
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="7"><div class="empty">
            @if ($search !== '' || $filter !== 'tous')
              <p>Aucun membre ne correspond à cette recherche.</p>
              <button type="button" class="link-btn" wire:click="resetFilters">Réinitialiser la recherche</button>
            @else
              <p>Aucun membre trouvé.</p>
            @endif
          </div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{ $members->links() }}

  <x-adm.modal :show="$showForm" title="Créer un compte">
    <form wire:submit="createMember" id="member-form">
      <div class="field">
        <label>Nom complet</label>
        <input type="text" wire:model="name">
        @error('name') <span class="inline-err">{{ $message }}</span> @enderror
      </div>
      <p class="muted" style="font-size:.78rem">Indiquez au moins l'un des deux : e-mail ou téléphone.</p>
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
      <div class="field-row">
        <div class="field">
          <label>Rôle</label>
          <select wire:model="role">
            <option value="apprenant">Apprenant</option>
            <option value="admin">Administrateur</option>
          </select>
        </div>
        <div class="field">
          <label>Ville</label>
          <input type="text" wire:model="city">
        </div>
      </div>
      <p class="muted" style="font-size:.78rem">Un mot de passe temporaire sera généré et affiché une seule fois.</p>
    </form>
    <x-slot:footer>
      <button type="button" class="btn ghost" wire:click="$set('showForm', false)">Annuler</button>
      <button type="submit" form="member-form" class="btn">Créer le compte</button>
    </x-slot:footer>
  </x-adm.modal>

</div>

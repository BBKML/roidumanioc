@php
    $p = $producerProfile;
@endphp
<div style="display:flex;flex-direction:column;gap:1.4rem">

  <div class="page-intro">
    <div>
      <a href="{{ route('admin.producers') }}" wire:navigate class="muted" style="font-size:.78rem">&larr; Producteurs</a>
      <h2>{{ $p->business_name }}</h2>
      <p>
        {{ $p->user->name }} · {{ $p->zone }} · {{ $p->activity_type->label() }}
        @if ($averageRating !== null)
          · {{ number_format($averageRating, 1) }}★ ({{ $reviewsCount }} avis)
        @endif
      </p>
    </div>
    <div style="display:flex;gap:.6rem;align-items:center">
      @if ($p->isVerified())
        <span class="pill ok"><span class="dot"></span>Vérifié</span>
      @else
        <span class="pill warn"><span class="dot"></span>À vérifier</span>
      @endif
    </div>
  </div>

  <div class="grid g-2">

    <div class="card pad-lg">
      <div class="card-head"><h3>Profil</h3></div>
      <dl class="kv">
        <dt>Compte</dt><dd>{{ $p->user->name }} — {{ $p->user->email ?? $p->user->phone }}</dd>
        <dt>Statut du compte</dt><dd><x-adm.pill :status="$p->user->status" /></dd>
        <dt>Zone</dt><dd>{{ $p->zone }}</dd>
        <dt>Activité</dt><dd>{{ $p->activity_type->label() }}</dd>
        <dt>Années d'activité</dt><dd>{{ $p->years_active ?? '—' }}</dd>
        <dt>Bio</dt><dd>{{ $p->bio ?: '—' }}</dd>
        @if ($p->isVerified())
          <dt>Vérifié le</dt><dd>{{ $p->verified_at->format('d/m/Y') }} par {{ $p->verifier?->name ?? '—' }}</dd>
        @endif
      </dl>
    </div>

    <div class="card pad-lg">
      <div class="card-head"><h3>Vérification d'identité</h3></div>
      @if (! $p->isVerified())
        <div x-data="{ seen: false }" style="display:flex;flex-direction:column;gap:.6rem;align-items:flex-start">
          <p class="muted" style="font-size:.84rem;margin:0">Le badge « Producteur vérifié ✅ » est affiché publiquement sur le catalogue.</p>
          <label class="switch-row" style="margin:0;font-size:.8rem">
            <input type="checkbox" x-model="seen">
            J'ai vérifié l'identité de ce producteur
          </label>
          <button class="btn" x-bind:disabled="!seen" wire:click="verify" data-confirm="Accorder le badge « Producteur vérifié » ?">
            Vérifier
          </button>
        </div>
      @else
        <p class="muted" style="font-size:.84rem">Vérifié depuis le {{ $p->verified_at->format('d/m/Y') }}.</p>
        <button class="btn ghost is-danger" wire:click="reject" data-confirm="Retirer le badge « Producteur vérifié » ?">
          Retirer la vérification
        </button>
      @endif
    </div>

  </div>

  <div class="card pad-lg">
    <div class="card-head"><h3>Offres ({{ $offers->count() }})</h3></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Produit</th><th>Quantité</th><th>Prix indicatif</th><th>Statut</th></tr></thead>
        <tbody>
          @forelse ($offers as $offer)
            <tr wire:key="offer-{{ $offer->id }}">
              <td>{{ $offer->product_name }}</td>
              <td class="muted">{{ $offer->quantity }} {{ $offer->unit->label() }}</td>
              <td class="muted">{{ $offer->price_indicative ? number_format($offer->price_indicative, 0, ',', ' ').' FCFA' : 'À convenir' }}</td>
              <td><x-adm.pill :status="$offer->status" /></td>
            </tr>
          @empty
            <tr><td colspan="4"><div class="empty"><p>Ce producteur n'a publié aucune offre pour l'instant.</p></div></td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="grid g-2">

    <div class="card pad-lg">
      <div class="card-head"><h3>Demandes ({{ $connectionRequests->total() }})</h3></div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Objet</th><th>Statut</th><th></th></tr></thead>
          <tbody>
            @forelse ($connectionRequests as $r)
              <tr wire:key="cr-{{ $r->id }}">
                <td>{{ $r->cropOffer->product_name ?? $r->buyerNeed?->product_wanted }}</td>
                <td><x-adm.pill :status="$r->status" /></td>
                <td><a class="iact" href="{{ route('learner.requests.show', $r) }}" wire:navigate title="Voir"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg></a></td>
              </tr>
            @empty
              <tr><td colspan="3"><div class="empty"><p>Aucune demande de mise en relation pour ce producteur pour l'instant.</p></div></td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      {{ $connectionRequests->links() }}
    </div>

    <div class="card pad-lg">
      <div class="card-head"><h3>Collaborations ({{ $collaborations->total() }})</h3></div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Acheteur</th><th>Statut</th><th></th></tr></thead>
          <tbody>
            @forelse ($collaborations as $c)
              <tr wire:key="collab-{{ $c->id }}">
                <td>{{ $c->buyerProfile->company_name ?: 'Acheteur' }}</td>
                <td><x-adm.pill :status="$c->status" /></td>
                <td><a class="iact" href="{{ route('learner.collaborations.show', $c) }}" wire:navigate title="Voir"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg></a></td>
              </tr>
            @empty
              <tr><td colspan="3"><div class="empty"><p>Aucune collaboration conclue avec ce producteur pour l'instant.</p></div></td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      {{ $collaborations->links() }}
    </div>

  </div>

</div>

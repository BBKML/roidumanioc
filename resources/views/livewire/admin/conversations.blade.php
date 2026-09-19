<div style="display:flex;flex-direction:column;gap:1.4rem">

  <div class="page-intro">
    <div>
      <h2>Conversations signalées</h2>
      <p>Messages où une tentative d'échange de coordonnées a été détectée (§16) — modération en lecture seule.</p>
    </div>
  </div>

  <div class="table-wrap">
    <table>
      <thead><tr><th>Demande</th><th>Producteur</th><th>Acheteur</th><th>Messages signalés</th><th></th></tr></thead>
      <tbody>
        @forelse ($conversations as $conversation)
          @php $cr = $conversation->connectionRequest; @endphp
          <tr class="row" wire:key="conv-{{ $conversation->id }}">
            <td>Demande #{{ $cr->id }}</td>
            <td class="muted">{{ $cr->producerProfile->business_name }}</td>
            <td class="muted">{{ $cr->buyerProfile->company_name ?: 'Acheteur' }}</td>
            <td>
              <span class="pill {{ $conversation->flagged_count >= \App\Models\Conversation::FLAG_THRESHOLD ? 'danger' : 'warn' }}">
                <span class="dot"></span>{{ $conversation->flagged_count }}
              </span>
            </td>
            <td>
              <a class="iact" href="{{ route('learner.requests.show', $cr) }}" wire:navigate title="Ouvrir la demande">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
              </a>
            </td>
          </tr>
        @empty
          <tr><td colspan="5"><div class="empty"><p>Aucune conversation signalée pour l'instant.</p></div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{ $conversations->links() }}

</div>

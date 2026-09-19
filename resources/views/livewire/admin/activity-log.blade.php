@php
    $labels = [
        'created' => 'Création',
        'updated' => 'Modification',
        'deleted' => 'Suppression',
        'password_reset_by_admin' => 'Réinit. mot de passe (admin)',
    ];
@endphp
<div style="display:flex;flex-direction:column;gap:1.4rem">

  <div class="page-intro">
    <div>
      <h2>Journal d'activité</h2>
      <p>Trace des actions sensibles : confirmations et refus de paiement, changements de rôle, suspensions, réinitialisations de mot de passe.</p>
    </div>
  </div>

  <div class="tabs">
    <button class="{{ $log === 'tous' ? 'on' : '' }}" wire:click="setLog('tous')">Tout</button>
    <button class="{{ $log === 'payment' ? 'on' : '' }}" wire:click="setLog('payment')">Paiements</button>
    <button class="{{ $log === 'user' ? 'on' : '' }}" wire:click="setLog('user')">Comptes</button>
  </div>
  <x-adm.loading-note target="setLog" />

  <div class="table-wrap stack-mobile">
    <table>
      <thead><tr><th>Quand</th><th>Par</th><th>Action</th><th>Sur</th><th>Détails</th></tr></thead>
      <tbody>
        @forelse ($entries as $e)
          <tr class="row" wire:key="log-{{ $e->id }}">
            <td class="muted card-title" style="white-space:nowrap">{{ $e->created_at->format('d/m/Y H:i') }}</td>
            <td data-label="Par">{{ $e->causer?->name ?? 'Système' }}</td>
            <td data-label="Action">
              <span class="pill neutral"><span class="dot"></span>{{ $labels[$e->description] ?? $e->description }}</span>
            </td>
            <td class="muted" data-label="Sur">
              @php $s = $e->subject; @endphp
              @if ($s instanceof \App\Models\Payment)
                Paiement {{ $s->reference }}
              @elseif ($s instanceof \App\Models\User)
                {{ $s->name }}
              @else
                {{ class_basename($e->subject_type) }} #{{ $e->subject_id }}
              @endif
            </td>
            <td style="font-size:.8rem" data-label="Détails">
              @php $changed = $e->properties['attributes'] ?? []; @endphp
              @if ($changed)
                @foreach ($changed as $k => $v)
                  <span class="muted">{{ $k }}</span> → <b>{{ is_scalar($v) ? $v : json_encode($v) }}</b>@if (! $loop->last) · @endif
                @endforeach
              @else
                —
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="5"><div class="empty"><p>
            @switch($log)
              @case('payment') Aucune action liée aux paiements enregistrée pour le moment. @break
              @case('user') Aucune action liée aux comptes enregistrée pour le moment. @break
              @default Aucune action sensible enregistrée pour le moment.
            @endswitch
          </p></div></td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{ $entries->links() }}

</div>

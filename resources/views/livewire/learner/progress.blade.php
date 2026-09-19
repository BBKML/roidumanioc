<div style="display:flex;flex-direction:column;gap:1.4rem">

  <div class="page-intro">
    <div>
      <h2>Ma progression</h2>
      <p>Votre avancement sur chaque formation suivie.</p>
    </div>
  </div>

  @if ($rows->isNotEmpty())
    <div class="table-wrap">
      <table>
        <thead><tr><th>Formation</th><th>Progression</th><th>%</th><th>Statut</th><th></th></tr></thead>
        <tbody>
          @foreach ($rows as $row)
            @php $pr = $row['progress']; @endphp
            <tr class="row" wire:key="prog-{{ $row['formation']->id }}">
              <td style="font-weight:700">{{ $row['formation']->title }}</td>
              <td style="min-width:180px"><div class="progress"><i style="width:{{ $pr['pct'] }}%"></i></div></td>
              <td class="nums">{{ $pr['pct'] }}%</td>
              <td>
                @if ($pr['pct'] === 100)
                  <span class="pill ok"><span class="dot"></span>Terminée</span>
                @else
                  <span class="pill warn"><span class="dot"></span>En cours</span>
                @endif
              </td>
              <td>
                <a class="btn sm ghost" href="{{ route('learner.course', $row['formation']) }}" wire:navigate>Ouvrir</a>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @else
    <div class="card"><div class="empty">
      <svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="9" r="6"/><path d="M9 14l-2 7 5-3 5 3-2-7"/></svg>
      <p>Inscrivez-vous à une formation pour suivre votre progression.</p>
      <a class="btn" href="{{ route('learner.catalog') }}" wire:navigate style="margin-top:.6rem">Voir le catalogue</a>
    </div></div>
  @endif

</div>

@php
    $stars = fn (int $n) => str_repeat('★', $n).str_repeat('☆', 5 - $n);
@endphp
<div style="display:flex;flex-direction:column;gap:1.4rem">

  @if ($myReview)
    <div class="card pad-lg">
      <div class="card-head"><h3>Votre évaluation</h3></div>
      <p style="font-size:1.15rem;font-weight:700;color:var(--gold)">{{ $stars($myReview->rating) }} <span class="muted" style="font-size:.86rem;font-weight:600">({{ $myReview->rating }}/5)</span></p>
      <ul class="reset" style="display:flex;flex-direction:column;gap:.3rem;margin-top:.8rem">
        @foreach ($criteriaLabels as $key => $label)
          <li style="font-size:.84rem;display:flex;justify-content:space-between;gap:1rem">
            <span class="muted">{{ $label }}</span>
            <b>{{ $myReview->criteria[$key] ?? '—' }}/5</b>
          </li>
        @endforeach
      </ul>
      @if ($myReview->comment)
        <p style="font-size:.86rem;margin-top:.8rem;white-space:pre-line">{{ $myReview->comment }}</p>
      @endif
    </div>
  @elseif ($canReview)
    <div class="card pad-lg">
      <div class="card-head"><h3>Évaluer cette collaboration</h3></div>
      <form wire:submit="submit">
        <div class="field">
          <label>Note globale</label>
          <select wire:model="rating">
            @foreach ([5, 4, 3, 2, 1] as $n)
              <option value="{{ $n }}">{{ $stars($n) }} ({{ $n }}/5)</option>
            @endforeach
          </select>
          @error('rating') <span class="inline-err">{{ $message }}</span> @enderror
        </div>

        <div class="field-row">
          @foreach ($criteriaLabels as $key => $label)
            <div class="field">
              <label>{{ $label }}</label>
              <select wire:model="criteria.{{ $key }}">
                @foreach ([5, 4, 3, 2, 1] as $n)
                  <option value="{{ $n }}">{{ $stars($n) }} ({{ $n }}/5)</option>
                @endforeach
              </select>
              @error('criteria.'.$key) <span class="inline-err">{{ $message }}</span> @enderror
            </div>
          @endforeach
        </div>

        <div class="field">
          <label>Commentaire <span class="muted">(facultatif)</span></label>
          <textarea wire:model="comment" rows="3" placeholder="Votre expérience avec cette collaboration…"></textarea>
          @error('comment') <span class="inline-err">{{ $message }}</span> @enderror
        </div>

        <p class="muted" style="font-size:.76rem">Votre évaluation ne pourra plus être modifiée une fois envoyée.</p>
        <button type="submit" class="btn">Envoyer mon évaluation</button>
      </form>
    </div>
  @endif

  @if ($theirReview)
    <div class="card pad-lg">
      <div class="card-head"><h3>Évaluation reçue</h3></div>
      <p style="font-size:1.15rem;font-weight:700;color:var(--gold)">{{ $stars($theirReview->rating) }} <span class="muted" style="font-size:.86rem;font-weight:600">({{ $theirReview->rating }}/5)</span></p>
      @if ($theirReview->comment)
        <p style="font-size:.86rem;margin-top:.8rem;white-space:pre-line">{{ $theirReview->comment }}</p>
      @endif
    </div>
  @endif

</div>

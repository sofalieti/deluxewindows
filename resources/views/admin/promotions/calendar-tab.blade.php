@php
  /** @var list<array{title: string, start: string, end: string, kind: string}> $periods */
  /** @var string|null $activeStart */
  /** @var string $today */
  $periods = $periods ?? [];
  $activeStart = $activeStart ?? null;
  $today = $today ?? now('America/Los_Angeles')->toDateString();
@endphp

<link rel="stylesheet" href="{{ asset('css/admin-promotions-calendar.css') }}">

<div class="promo-calendar-tab">
  <p class="promo-calendar-tab__intro">
    Year-long calendar (America/Los_Angeles): all U.S. holidays + seasonal fillers.
    <strong>Max period length: 2 weeks</strong>. A job every 3 hours sets
    <strong>Global Promotion Title</strong> from the active row.
  </p>

  <div class="promo-calendar-tab__toolbar">
    <span class="text-muted small">Today: {{ $today }} · Use the top-bar buttons to regenerate or apply now.</span>
  </div>

  <div class="promo-calendar-table-wrap">
    <table class="promo-calendar-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Promotion Title</th>
          <th>Start</th>
          <th>End</th>
          <th>Type</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        @forelse($periods as $index => $period)
          @php
            $isActive = $activeStart !== null && $period['start'] === $activeStart;
            $kind = $period['kind'] ?? 'custom';
          @endphp
          <tr class="{{ $isActive ? 'is-active' : '' }}">
            <td>{{ $index + 1 }}</td>
            <td>
              <input
                type="text"
                class="form-control form-control-sm"
                name="promotions[calendar_periods][{{ $index }}][title]"
                value="{{ $period['title'] }}"
                required
              />
              <input type="hidden" name="promotions[calendar_periods][{{ $index }}][kind]" value="{{ $kind }}" />
            </td>
            <td>
              <input
                type="date"
                class="form-control form-control-sm"
                name="promotions[calendar_periods][{{ $index }}][start]"
                value="{{ $period['start'] }}"
                required
              />
            </td>
            <td>
              <input
                type="date"
                class="form-control form-control-sm"
                name="promotions[calendar_periods][{{ $index }}][end]"
                value="{{ $period['end'] }}"
                required
              />
            </td>
            <td>
              <span class="promo-calendar-table__kind promo-calendar-table__kind--{{ $kind }}">{{ $kind }}</span>
            </td>
            <td>
              @if($isActive)
                <span class="promo-calendar-table__status promo-calendar-table__status--active">Active</span>
              @elseif($today < $period['start'])
                <span class="promo-calendar-table__status">Upcoming</span>
              @else
                <span class="promo-calendar-table__status">Past</span>
              @endif
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6">No periods yet. Click “Regenerate calendar” in the top bar.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <p class="promo-calendar-tab__hint">
    Edit titles/dates above and click <strong>Save promotions</strong>.
    Scheduler command: <code>php artisan promotions:apply-calendar</code> (every 3 hours).
  </p>
</div>

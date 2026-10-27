@php
    /** @var \App\Models\SiteVisit $visit */
    /** @var \Illuminate\Support\Collection<int, \App\Models\SiteEvent> $events */
    $events = $events ?? collect();
    $clarityUrl = $clarityUrl ?? null;
@endphp

<div class="bg-white rounded shadow-sm p-4 mb-3">
    <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
        <h6 class="text-muted text-uppercase mb-0">Behavior</h6>
        @if($clarityUrl)
            <a href="{{ $clarityUrl }}" target="_blank" rel="noopener">Open in Clarity</a>
        @endif
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3"><div class="text-muted small">Pages</div><strong>{{ (int) $visit->pageviews }}</strong></div>
        <div class="col-6 col-md-3"><div class="text-muted small">Engaged</div><strong>{{ $visit->engaged_seconds ? $visit->engagedLabel() : '—' }}</strong></div>
        <div class="col-6 col-md-3"><div class="text-muted small">Max scroll</div><strong>{{ $visit->max_scroll ? $visit->max_scroll.'%' : '—' }}</strong></div>
        <div class="col-6 col-md-3"><div class="text-muted small">Rage clicks</div><strong>{{ (int) $visit->rage_clicks }}</strong></div>
    </div>

    <div class="text-muted small mb-2">
        Visitor {{ $visit->visitor_id ?: '—' }}
        · Session {{ $visit->session_id ?: '—' }}
        @if($visit->is_bot)
            · <span class="badge bg-secondary">bot</span>
        @endif
    </div>

    @forelse($events as $event)
        <div class="d-flex justify-content-between gap-3 border-bottom py-2">
            <div>
                <strong>{{ $event->label() }}</strong>
                @if($event->target)
                    <span class="text-muted">{{ $event->target }}</span>
                @endif
                <div class="small text-muted">{{ $event->path ?: '—' }}</div>
            </div>
            <span class="text-muted small text-nowrap">
                {{ optional($event->created_at)->timezone('America/Los_Angeles')->format('h:i:s A') }}
            </span>
        </div>
    @empty
        <p class="text-muted mb-0">No behavior events for this session yet.</p>
    @endforelse
</div>

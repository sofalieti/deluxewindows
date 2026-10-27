@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\SiteVisit> $journey */
    $journey = $journey ?? collect();
    $clarityUrl = $clarityUrl ?? null;
@endphp

<div class="bg-white rounded shadow-sm p-4 mb-3">
    <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
        <h6 class="text-muted text-uppercase mb-0">Customer path</h6>
        @if($clarityUrl)
            <a href="{{ $clarityUrl }}" target="_blank" rel="noopener">Open in Clarity</a>
        @endif
    </div>

    @if($journey->isEmpty())
        <p class="text-muted mb-0">No visits are linked to this visitor yet.</p>
    @else
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Source</th>
                        <th>Landing</th>
                        <th>Pages</th>
                        <th>Time</th>
                        <th>Scroll</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($journey as $visit)
                        <tr>
                            <td class="text-nowrap">
                                <a href="{{ route('platform.visits.view', $visit) }}">
                                    {{ optional($visit->created_at)->timezone('America/Los_Angeles')->format('M d, Y h:i A') }}
                                </a>
                            </td>
                            <td>{{ $visit->trafficSourceLabel() }}</td>
                            <td>{{ \Illuminate\Support\Str::limit((string) ($visit->landing_page ?: $visit->page_url ?: '—'), 48) }}</td>
                            <td>{{ (int) $visit->pageviews }}</td>
                            <td>{{ $visit->engaged_seconds ? $visit->engagedLabel() : '—' }}</td>
                            <td>{{ $visit->max_scroll ? $visit->max_scroll.'%' : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

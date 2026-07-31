@php
    /** @var list<array{key: string, label: string, color: string, count: int}> $trafficSummary */
    $trafficSummary = $trafficSummary ?? [];
@endphp

<div class="bg-white rounded shadow-sm p-4 contact-panel">
    <h6 class="text-muted text-uppercase mb-3 contact-panel__title">Lead source summary</h6>

    @if ($trafficSummary !== [])
        <div class="contact-traffic-summary">
            @foreach ($trafficSummary as $source)
                <div class="contact-traffic-summary__item">
                    <span class="badge bg-{{ $source['color'] }} text-white">{{ $source['label'] }}</span>
                    <strong>{{ $source['count'] }}</strong>
                </div>
            @endforeach
        </div>
        <p class="small text-muted mt-3 mb-0">
            Total leads: {{ collect($trafficSummary)->sum('count') }}. Detailed UTM and click data remains on each lead.
        </p>
    @else
        <p class="text-muted mb-0">No source data yet.</p>
    @endif
</div>

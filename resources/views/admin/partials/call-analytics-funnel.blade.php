@php
    /** @var array{visits: int, phone_clicks: int, confirmed_calls: int, connected: int, leads: int, contacts: int} $funnel */
    $funnel = $funnel ?? [];
    $steps = [
        ['label' => 'Visits', 'value' => (int) ($funnel['visits'] ?? 0)],
        ['label' => 'Phone clicks', 'value' => (int) ($funnel['phone_clicks'] ?? 0)],
        ['label' => 'Confirmed calls', 'value' => (int) ($funnel['confirmed_calls'] ?? 0)],
        ['label' => 'Connected', 'value' => (int) ($funnel['connected'] ?? 0)],
        ['label' => 'Leads', 'value' => (int) ($funnel['leads'] ?? 0)],
        ['label' => 'Contacts', 'value' => (int) ($funnel['contacts'] ?? 0)],
    ];
    $max = max(1, ...array_column($steps, 'value'));
@endphp

<div class="call-analytics-funnel">
    @foreach ($steps as $step)
        @php $width = max(4, (int) round(($step['value'] / $max) * 100)); @endphp
        <div class="mb-3">
            <div class="d-flex justify-content-between small mb-1">
                <span>{{ $step['label'] }}</span>
                <strong>{{ number_format($step['value']) }}</strong>
            </div>
            <div class="progress">
                <div
                    class="progress-bar bg-primary"
                    role="progressbar"
                    style="--funnel-width: {{ $width }}%;"
                    aria-valuenow="{{ $step['value'] }}"
                    aria-valuemin="0"
                    aria-valuemax="{{ $max }}"
                ></div>
            </div>
        </div>
    @endforeach
</div>

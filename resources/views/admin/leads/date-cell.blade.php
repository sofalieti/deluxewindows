@php
    /** @var \App\Models\Lead $lead */
    $city = trim((string) ($lead->city ?? ''));
    $source = $lead->trafficSourceLabel();
@endphp

<div class="lead-date-cell">
    <div class="lead-date-cell__date">{{ optional($lead->created_at)->format('Y-m-d H:i') }}</div>
    @if ($city !== '')
        <div class="lead-date-cell__city">{{ $city }}</div>
    @endif
    <div class="lead-date-cell__source">{{ $source }}</div>
</div>

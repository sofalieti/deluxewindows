@php
    /** @var \App\Models\Lead $lead */
    /** @var \Orchid\Screen\Action $dropdown */
    $color = $lead->statusColor();
@endphp

<div class="lead-status-cell">
    <span class="lead-status-badge lead-status-badge--{{ $color }}">{{ $lead->statusLabel() }}</span>
    {!! $dropdown !!}
</div>

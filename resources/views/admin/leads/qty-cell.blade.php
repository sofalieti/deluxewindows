@php
    /** @var \App\Models\Lead $lead */
    $summary = $lead->openingSummary();
@endphp
@if ($summary !== '')
    <span class="lead-qty">{{ $summary }}</span>
@else
    <span class="text-muted">—</span>
@endif

@php
    /** @var \App\Models\Contact $contact */
    $city = trim((string) ($contact->city ?? ''));
    $traffic = collect($contact->trafficSummary())
        ->map(fn (array $item): string => $item['count'].' '.$item['label'])
        ->implode(' · ');
@endphp

<div class="lead-date-cell">
    <div class="lead-date-cell__date">{{ optional($contact->created_at)->format('Y-m-d H:i') }}</div>
    @if ($city !== '')
        <div class="lead-date-cell__city">{{ $city }}</div>
    @endif
    @if ($traffic !== '')
        <div class="lead-date-cell__source">{{ $traffic }}</div>
    @endif
</div>

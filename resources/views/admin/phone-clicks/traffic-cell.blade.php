@php
    /** @var \App\Models\PhoneClick $click */
    $lastDetail = $click->trafficSourceDetail();
    $firstDetail = $click->firstTrafficSourceDetail();
@endphp
<div class="phone-click-traffic">
    <div class="phone-click-traffic__block">
        <div class="phone-click-traffic__label">Last</div>
        <span class="badge bg-{{ $click->trafficSourceColor() }} text-white">{{ $click->trafficSourceLabel() }}</span>
        @if ($lastDetail !== '')
            <div class="phone-click-traffic__detail">{{ \Illuminate\Support\Str::limit($lastDetail, 24) }}</div>
        @endif
    </div>

    <div class="phone-click-traffic__block">
        <div class="phone-click-traffic__label">First</div>
        <span class="badge bg-{{ $click->firstTrafficSourceColor() }} text-white">{{ $click->firstTrafficSourceLabel() }}</span>
        @if ($firstDetail !== '')
            <div class="phone-click-traffic__detail">{{ \Illuminate\Support\Str::limit($firstDetail, 24) }}</div>
        @endif
    </div>

    <div class="phone-click-traffic__google">
        @if ($click->wasSentToGoogleSheet())
            <span class="badge bg-success text-white">✓ Sent {{ optional($click->google_sheet_sent_at)->format('Y-m-d H:i') }}</span>
        @elseif (! empty($sendToGoogle))
            {!! $sendToGoogle !!}
        @endif
    </div>
</div>

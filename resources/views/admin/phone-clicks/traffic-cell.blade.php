@php
    /** @var \App\Models\PhoneClick $click */
    $lastDetail = $click->trafficSourceDetail();
    $firstDetail = $click->firstTrafficSourceDetail();
@endphp
<div class="phone-click-traffic">
    <div class="phone-click-traffic__row">
        <span class="phone-click-traffic__label">Last</span>
        <span class="badge bg-{{ $click->trafficSourceColor() }} text-white">{{ $click->trafficSourceLabel() }}</span>
        @if ($lastDetail !== '')
            <span class="phone-click-traffic__detail">{{ \Illuminate\Support\Str::limit($lastDetail, 16) }}</span>
        @endif
    </div>
    <div class="phone-click-traffic__row">
        <span class="phone-click-traffic__label">First</span>
        <span class="badge bg-{{ $click->firstTrafficSourceColor() }} text-white">{{ $click->firstTrafficSourceLabel() }}</span>
        @if ($firstDetail !== '')
            <span class="phone-click-traffic__detail">{{ \Illuminate\Support\Str::limit($firstDetail, 16) }}</span>
        @endif
    </div>
</div>

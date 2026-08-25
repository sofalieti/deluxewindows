@php
    /** @var \App\Models\PhoneClick $click */
    $status = (string) ($click->ringcentral_status ?: \App\Models\PhoneClick::RINGCENTRAL_NOT_CHECKED);
    $color = $click->ringCentralStatusColor();
@endphp

<div class="phone-click-rc">
    <span class="lead-status-badge lead-status-badge--{{ $color }}">
        {{ $click->ringCentralStatusLabel() }}
    </span>

    @if ($status === \App\Models\PhoneClick::RINGCENTRAL_FOUND)
        <div class="phone-click-rc__meta">
            @if ($click->ringcentral_call_started_at)
                Call {{ $click->ringcentral_call_started_at->copy()->timezone('America/Los_Angeles')->format('h:i A') }} PT
                ·
            @endif
            {{ $click->ringCentralDurationLabel() }}
            · {{ $click->ringcentral_from_phone ?: 'Unknown caller' }}
            @if ($click->metaValue('ringcentral_match_lag_seconds') !== '')
                · +{{ $click->metaValue('ringcentral_match_lag_seconds') }}s after click
            @endif
        </div>

        @if ($click->hasRecording())
            @include('admin.partials.call-recording', ['url' => $click->recordingUrl(), 'compact' => true])
        @endif

        @php $matchedCall = $click->ringCentralCall(); @endphp
        @if ($matchedCall !== null)
            @include('admin.partials.call-transcript', ['call' => $matchedCall, 'compact' => true, 'canQueue' => false])
        @endif
    @endif
</div>

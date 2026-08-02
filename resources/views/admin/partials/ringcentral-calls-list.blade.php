@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\RingCentralCall>|\App\Models\RingCentralCall[] $calls */
    $calls = $calls ?? collect();
    $phone = trim((string) ($phone ?? ''));
    $emptyHint = (string) ($emptyHint ?? 'No RingCentral calls found for this phone number yet.');
@endphp

<div class="bg-white rounded shadow-sm p-4 mb-3 contact-calls">
    <h6 class="text-muted text-uppercase mb-3 contact-panel__title">RingCentral calls</h6>

    @if ($phone === '')
        <p class="text-muted mb-0">Add a phone number to see matching call history.</p>
    @else
        <p class="text-muted small mb-3">
            History for
            <a href="tel:{{ $phone }}">{{ $phone }}</a>
            (inbound and outbound).
        </p>

        @forelse ($calls as $call)
            @php
                $started = $call->startedAtPacific();
                $isInbound = $call->direction === 'Inbound';
            @endphp
            <div class="contact-call {{ $loop->last ? '' : 'contact-call--border' }}">
                <div class="contact-call__main">
                    <div class="contact-call__when">
                        <div class="contact-call__date">{{ $started ? $started->format('M d, Y') : '—' }}</div>
                        <div class="contact-call__time">{{ $started ? $started->format('h:i A').' PT' : '—' }}</div>
                    </div>
                    <div class="contact-call__meta">
                        <span class="badge {{ $isInbound ? 'bg-success' : 'bg-primary' }} text-white">
                            {{ $isInbound ? 'Inbound' : 'Outbound' }}
                        </span>
                        <div class="contact-call__result">
                            {{ $call->result ?: 'Unknown' }}
                            <span class="text-muted">· {{ $call->durationLabel() }}</span>
                        </div>
                        <div class="contact-call__route text-muted">
                            {{ $call->from_phone ?: '—' }} → {{ $call->to_phone ?: '—' }}
                        </div>
                        @include('admin.partials.call-recording', ['url' => $call->recordingUrl()])
                        @include('admin.partials.call-transcript', [
                            'call' => $call,
                            'compact' => false,
                            'canQueue' => true,
                        ])
                    </div>
                </div>
            </div>
        @empty
            <p class="text-muted mb-0">{{ $emptyHint }}</p>
        @endforelse
    @endif
</div>

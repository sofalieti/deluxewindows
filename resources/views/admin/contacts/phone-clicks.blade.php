@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\PhoneClick> $phoneClicks */
    $phoneClicks = $phoneClicks ?? collect();
@endphp

<div class="bg-white rounded shadow-sm p-4 contact-panel">
    <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
        <h6 class="text-muted text-uppercase mb-0 contact-panel__title">Phone clicks</h6>
        <span class="badge bg-primary text-white">{{ $phoneClicks->count() }}</span>
    </div>

    @forelse ($phoneClicks as $click)
        <div class="contact-lead {{ $loop->last ? '' : 'border-bottom' }}">
            <div class="d-flex justify-content-between align-items-start gap-3">
                <div>
                    <a class="fw-semibold" href="{{ route('platform.phone-clicks.view', $click) }}">
                        Click #{{ $click->id }}
                    </a>
                    <span class="lead-status-badge lead-status-badge--{{ $click->handlingStatusColor() }}">
                        {{ $click->handlingStatusLabel() }}
                    </span>
                    <div class="small text-muted mt-1">
                        {{ $click->ringcentral_status }}
                        @if ($click->ringcentral_result)
                            · {{ $click->ringcentral_result }}
                        @endif
                    </div>
                </div>
                <span class="small text-muted">{{ optional($click->created_at)->format('Y-m-d H:i') }}</span>
            </div>
        </div>
    @empty
        <p class="text-muted mb-0">No phone clicks are linked to this contact yet.</p>
    @endforelse
</div>

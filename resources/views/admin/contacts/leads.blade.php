@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\Lead> $leads */
    $leads = $leads ?? collect();
@endphp

<div class="bg-white rounded shadow-sm p-4 contact-panel">
    <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
        <h6 class="text-muted text-uppercase mb-0 contact-panel__title">Linked leads</h6>
        <span class="badge bg-primary text-white">{{ $leads->count() }}</span>
    </div>

    @forelse ($leads as $lead)
        <div class="contact-lead {{ $loop->last ? '' : 'border-bottom' }}">
            <div class="d-flex justify-content-between align-items-start gap-3">
                <div>
                    <a class="fw-semibold" href="{{ route('platform.leads.edit', $lead) }}">
                        Lead #{{ $lead->id }}
                    </a>
                    <span class="lead-status-badge lead-status-badge--{{ $lead->statusColor() }}">
                        {{ $lead->statusLabel() }}
                    </span>
                </div>
                <span class="small text-muted">{{ optional($lead->created_at)->format('Y-m-d H:i') }}</span>
            </div>
            @if (filled($lead->message))
                <div class="small mt-2 text-break">{{ \Illuminate\Support\Str::words($lead->message, 18, '…') }}</div>
            @endif
        </div>
    @empty
        <p class="text-muted mb-0">No leads are linked to this contact yet.</p>
    @endforelse
</div>

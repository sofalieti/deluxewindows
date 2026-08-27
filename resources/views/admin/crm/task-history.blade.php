@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\CrmTaskEvent>|\App\Models\CrmTaskEvent[] $events */
    $events = $events ?? collect();
@endphp

<div class="bg-white rounded shadow-sm p-4 mb-3 lead-comments">
    <h6 class="text-muted text-uppercase mb-3 lead-comments__title">History</h6>

    @forelse ($events as $event)
        <div class="border-bottom py-3 {{ $loop->last ? 'border-0' : '' }}">
            <div class="d-flex justify-content-between align-items-baseline gap-2 mb-1">
                <strong>{{ $event->actionLabel() }} · {{ $event->user?->name ?? 'System' }}</strong>
                <span class="text-muted small">{{ optional($event->created_at)->format('Y-m-d H:i') }}</span>
            </div>
            @if (filled($event->comment))
                <div class="text-break">{!! nl2br(e($event->comment)) !!}</div>
            @endif
        </div>
    @empty
        <p class="text-muted mb-0">No history yet.</p>
    @endforelse
</div>

@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\LeadChange>|\App\Models\LeadChange[] $changes */
    $changes = $changes ?? collect();
@endphp

<div class="bg-white rounded shadow-sm p-4 mb-3 lead-history">
    <h6 class="text-muted text-uppercase mb-3 lead-comments__title">Change history</h6>

    @forelse ($changes as $change)
        <div class="border-bottom py-3 {{ $loop->last ? 'border-0' : '' }}">
            <div class="d-flex justify-content-between align-items-baseline gap-2 mb-1">
                <strong>{{ $change->user?->name ?? 'System' }}</strong>
                <span class="text-muted small">{{ optional($change->created_at)->format('Y-m-d H:i') }}</span>
            </div>
            <div class="text-break">{{ $change->summary }}</div>
            @if ($change->field === 'comment' && filled($change->new_value))
                <div class="text-muted small mt-1 text-break">{{ $change->new_value }}</div>
            @endif
        </div>
    @empty
        <p class="text-muted mb-0">No changes recorded yet.</p>
    @endforelse
</div>

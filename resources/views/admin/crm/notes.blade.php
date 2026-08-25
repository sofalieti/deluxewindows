@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\CrmNote>|\App\Models\CrmNote[] $notes */
    $notes = $notes ?? collect();
@endphp

<div class="bg-white rounded shadow-sm p-4 mb-3 lead-comments">
    <h6 class="text-muted text-uppercase mb-3 lead-comments__title">Notes</h6>

    @forelse ($notes as $note)
        <div class="border-bottom py-3 {{ $loop->last ? 'border-0' : '' }}">
            <div class="d-flex justify-content-between align-items-baseline gap-2 mb-1">
                <strong>{{ $note->user?->name ?? 'Unknown user' }}</strong>
                <span class="text-muted small">{{ optional($note->created_at)->format('Y-m-d H:i') }}</span>
            </div>
            <div class="text-break">{!! nl2br(e($note->body)) !!}</div>
        </div>
    @empty
        <p class="text-muted mb-0">No notes yet.</p>
    @endforelse
</div>

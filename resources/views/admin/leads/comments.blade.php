@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\LeadComment>|\App\Models\LeadComment[] $comments */
    $comments = $comments ?? collect();
@endphp

<div class="bg-white rounded shadow-sm p-4 mb-3 lead-comments">
    <h6 class="text-muted text-uppercase mb-3 lead-comments__title">Comment history</h6>

    @forelse ($comments as $comment)
        <div class="border-bottom py-3 {{ $loop->last ? 'border-0' : '' }}">
            <div class="d-flex justify-content-between align-items-baseline gap-2 mb-1">
                <strong>{{ $comment->user?->name ?? 'Unknown user' }}</strong>
                <span class="text-muted small">{{ optional($comment->created_at)->format('Y-m-d H:i') }}</span>
            </div>
            <div class="text-break">{!! nl2br(e($comment->body)) !!}</div>
        </div>
    @empty
        <p class="text-muted mb-0">No comments yet.</p>
    @endforelse
</div>

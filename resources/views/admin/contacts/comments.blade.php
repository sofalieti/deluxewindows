@php
    /** @var \Illuminate\Support\Collection<int, object> $comments */
    $comments = $comments ?? collect();
@endphp

<div class="bg-white rounded shadow-sm p-4 contact-panel">
    <h6 class="text-muted text-uppercase mb-3 contact-panel__title">Comment history</h6>

    @forelse ($comments as $comment)
        <div class="contact-comment {{ $loop->last ? '' : 'border-bottom' }}">
            <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
                <div>
                    @if (($comment->type ?? '') === 'lead' && filled($comment->lead_id))
                        <a class="badge bg-primary text-white text-decoration-none" href="{{ route('platform.leads.edit', $comment->lead_id) }}">
                            Lead #{{ $comment->lead_id }}
                        </a>
                    @else
                        <span class="badge bg-secondary text-white">Contact</span>
                    @endif
                    <strong class="ms-1">{{ $comment->user?->name ?? 'Unknown user' }}</strong>
                </div>
                <span class="small text-muted">{{ optional($comment->created_at)->format('Y-m-d H:i') }}</span>
            </div>
            <div class="text-break">{!! nl2br(e($comment->body)) !!}</div>
        </div>
    @empty
        <p class="text-muted mb-0">No comments yet.</p>
    @endforelse
</div>

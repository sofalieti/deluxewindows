@php
    /** @var \Illuminate\Support\Collection<int, object> $timeline */
    $timeline = $timeline ?? collect();
@endphp

<div class="bg-white rounded shadow-sm p-4 contact-panel">
    <h6 class="text-muted text-uppercase mb-3 contact-panel__title">Activity</h6>

    @forelse ($timeline as $item)
        <div class="border-bottom py-3 {{ $loop->last ? 'border-0' : '' }}">
            <div class="d-flex justify-content-between align-items-baseline gap-2 mb-1">
                <strong>
                    @if (! empty($item->url))
                        <a href="{{ $item->url }}">{{ $item->title }}</a>
                    @else
                        {{ $item->title }}
                    @endif
                </strong>
                <span class="text-muted small">{{ optional($item->created_at)->format('Y-m-d H:i') }}</span>
            </div>
            <div class="small text-muted">{{ $item->user_name }}</div>
            @if (filled($item->body))
                <div class="text-break mt-1">{!! nl2br(e($item->body)) !!}</div>
            @endif
        </div>
    @empty
        <p class="text-muted mb-0">No activity yet.</p>
    @endforelse
</div>

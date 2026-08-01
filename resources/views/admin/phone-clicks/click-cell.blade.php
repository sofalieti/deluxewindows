@php
    $clickAt = $click->created_at
        ? $click->created_at->copy()->timezone('America/Los_Angeles')
        : null;
    $phone = trim((string) ($click->phone ?? ''));
@endphp
<div class="phone-click-cell">
    <div class="phone-click-cell__date">
        {{ $clickAt ? $clickAt->format('M d, Y') : '—' }}
    </div>
    <div class="phone-click-cell__phone">
        @if ($phone !== '')
            <a href="tel:{{ $phone }}">{{ $phone }}</a>
        @else
            <span>No number</span>
        @endif
    </div>
    <div class="phone-click-cell__time">
        {{ $clickAt ? $clickAt->format('h:i A').' PT' : '—' }}
    </div>
</div>

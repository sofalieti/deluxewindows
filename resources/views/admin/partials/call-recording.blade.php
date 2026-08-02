@php
    /** @var string|null $url */
    $url = isset($url) ? trim((string) $url) : null;
    $compact = (bool) ($compact ?? false);
@endphp

@if ($url)
    <div class="call-recording {{ $compact ? 'call-recording--compact' : '' }}">
        <a class="call-recording__link" href="{{ $url }}" target="_blank" rel="noopener">Listen</a>
        <audio class="call-recording__player" controls preload="none" src="{{ $url }}"></audio>
    </div>
@endif

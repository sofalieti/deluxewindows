@php
    /** @var \App\Models\PhoneClick $click */
    $url = trim((string) ($click->page_url ?? ''));
    $landing = trim((string) ($click->landing_page ?? ''));
    $source = trim((string) ($click->source_label ?? ''));
    $path = $url !== ''
        ? ((string) parse_url($url, PHP_URL_PATH) ?: $url)
        : $landing;
@endphp
<div class="phone-click-page">
    @if ($url === '' && $landing === '')
        <span class="text-muted">—</span>
    @elseif ($url !== '')
        <a href="{{ $url }}" target="_blank" rel="noopener">{{ \Illuminate\Support\Str::limit($path, 36) }}</a>
    @else
        <span>{{ \Illuminate\Support\Str::limit($path, 36) }}</span>
    @endif

    @if ($landing !== '' && $landing !== $path)
        <div class="phone-click-page__landing">{{ \Illuminate\Support\Str::limit($landing, 36) }}</div>
    @endif

    <div class="phone-click-page__source">{{ $source !== '' ? $source : 'Unknown source' }}</div>
</div>

@props([
    'src' => '',
    'preset' => 'card',
    'alt' => '',
    'loading' => 'lazy',
    'width' => null,
    'height' => null,
    'sizes' => null,
])

@php
    $originalSrc = is_string($src) ? trim($src) : '';
    try {
        $responsive = thumbnail_responsive(
            $originalSrc,
            $preset,
            is_numeric($width) ? (int) $width : null,
            is_numeric($height) ? (int) $height : null,
            $sizes,
        );
    } catch (\Throwable) {
        $responsive = ['src' => $originalSrc, 'srcset' => null, 'sizes' => null];
    }
    $imgSrc = $responsive['src'] !== '' ? $responsive['src'] : $originalSrc;
@endphp

@if($imgSrc !== '')
<img
    src="{{ $imgSrc }}"
    @if($responsive['srcset']) srcset="{{ $responsive['srcset'] }}" @endif
    @if($responsive['sizes']) sizes="{{ $responsive['sizes'] }}" @endif
    alt="{{ $alt }}"
    loading="{{ $loading }}"
    @if($width) width="{{ $width }}" @endif
    @if($height) height="{{ $height }}" @endif
    {{ $attributes }}
/>
@endif

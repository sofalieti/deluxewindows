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
    $responsive = thumbnail_responsive(
        $src,
        $preset,
        is_numeric($width) ? (int) $width : null,
        is_numeric($height) ? (int) $height : null,
        $sizes,
    );
@endphp

@if($responsive['src'] !== '')
<img
    src="{{ $responsive['src'] }}"
    @if($responsive['srcset']) srcset="{{ $responsive['srcset'] }}" @endif
    @if($responsive['sizes']) sizes="{{ $responsive['sizes'] }}" @endif
    alt="{{ $alt }}"
    loading="{{ $loading }}"
    @if($width) width="{{ $width }}" @endif
    @if($height) height="{{ $height }}" @endif
    {{ $attributes }}
/>
@endif

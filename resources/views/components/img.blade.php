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
    $presetName = is_string($preset) ? $preset : 'card';
    $attrWidth = is_numeric($width) ? (int) $width : null;
    $attrHeight = is_numeric($height) ? (int) $height : null;

    try {
        $responsive = thumbnail_responsive(
            $originalSrc,
            $presetName,
            $attrWidth,
            $attrHeight,
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
    @if($attrWidth) width="{{ $attrWidth }}" @endif
    @if($attrHeight) height="{{ $attrHeight }}" @endif
    {{ $attributes }}
/>
@endif

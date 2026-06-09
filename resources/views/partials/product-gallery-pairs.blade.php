@php
    $galleryUrl = function ($url) {
        try {
            return thumbnail_url($url, 'gallery_main') ?: $url;
        } catch (\Throwable) {
            return $url;
        }
    };

    $images = collect();
    $primary = is_string($primaryImage ?? null) && ($primaryImage ?? '') !== '' ? $primaryImage : null;

    if ($primary) {
        $images->push($primary);
    }

    foreach ($galleryImages ?? [] as $img) {
        if (is_string($img) && $img !== '' && ! $images->contains($img)) {
            $images->push($img);
        }
    }

    $pairs = $images->chunk(2);
    $altBase = $title ?? 'Product';
@endphp

@if($images->isNotEmpty())
@once
<style>
  .dw-gallery-pairs,
  .dw-gallery-pair,
  .dw-gallery-pair__cell {
    border: none;
    outline: none;
    box-shadow: none;
  }
  .image-wrapper.border-radius-image-default:has(.dw-gallery-pairs) {
    border: none;
    outline: none;
    box-shadow: none;
  }
  .dw-gallery-pairs {
    display: flex;
    flex-direction: column;
    gap: 12px;
    width: 100%;
  }
  .dw-gallery-pair {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    width: 100%;
  }
  .dw-gallery-pair--single {
    grid-template-columns: 1fr;
  }
  .dw-gallery-pair__cell {
    aspect-ratio: 610 / 343;
    overflow: hidden;
    border-radius: 12px;
    background: transparent;
  }
  .dw-gallery-pair__cell img {
    width: 100%;
    height: 100%;
    max-height: none !important;
    aspect-ratio: auto !important;
    min-height: 100% !important;
    object-fit: cover;
    display: block;
    border: none !important;
    outline: none;
    box-shadow: none;
  }
  @media (max-width: 767px) {
    .dw-gallery-pair {
      grid-template-columns: 1fr;
    }
  }
</style>
@endonce
<div class="image-wrapper border-radius-image-default">
  <div class="dw-gallery-pairs" id="dw-gallery">
    @foreach($pairs as $pairIndex => $pair)
    <div class="dw-gallery-pair{{ $pair->count() === 1 ? ' dw-gallery-pair--single' : '' }}">
      @foreach($pair->values() as $cellIndex => $img)
      <div class="dw-gallery-pair__cell dw-gallery-pair__cell--{{ $cellIndex === 0 ? 'wall' : 'spec' }}">
        <img
          src="{{ $galleryUrl($img) }}"
          alt="{{ $altBase }} — {{ $cellIndex === 0 ? 'installation photo' : 'specification' }} {{ $pairIndex + 1 }}"
          loading="{{ $pairIndex === 0 && $cellIndex === 0 ? 'eager' : 'lazy' }}"
          class="image cover-image"
        />
      </div>
      @endforeach
    </div>
    @endforeach
  </div>
</div>
@endif

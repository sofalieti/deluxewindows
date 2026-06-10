@php
    $logoSrc = is_string($image ?? null) ? trim($image) : '';
    $logoAlt = $alt ?? '';
    $loadingAttr = $loading ?? 'eager';
@endphp

@once
<style>
  .dw-brand-logo-slot.image-wrapper.border-radius-image-default.property-card-top-content-v1---image,
  .dw-brand-logo-slot.image-wrapper.is-brand-logo {
    background: #f3f4f6;
    border-radius: 12px;
    border: none !important;
    border-bottom: none !important;
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 100%;
    width: 100%;
    height: 80px;
    min-height: 80px;
    max-height: 80px;
    padding: 12px 16px;
    overflow: hidden;
  }

  .dw-brand-logo-slot .dw-brand-logo-img {
    mix-blend-mode: darken;
    object-fit: contain;
    width: auto !important;
    max-width: 65% !important;
    min-width: 0 !important;
    max-height: 52px !important;
    height: auto !important;
    aspect-ratio: auto !important;
    min-height: 0 !important;
    border-radius: 0 !important;
  }
</style>
@endonce

<div class="image-wrapper border-radius-image-default property-card-top-content-v1---image is-brand-logo dw-brand-logo-slot">
  @if($logoSrc !== '')
  <x-img
    :src="$logoSrc"
    preset="brand_grid"
    :loading="$loadingAttr"
    :alt="$logoAlt"
    class="image cover-image is-brandlogo dw-brand-logo-img"
  />
  @endif
</div>

@php
    $logoSrc = is_string($image ?? null) ? trim($image) : '';
    $logoAlt = $alt ?? '';
    $loadingAttr = $loading ?? 'eager';
@endphp

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

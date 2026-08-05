@php
    $localPhone = $localPhone ?? null;
    $localLabel = trim((string) ($localLabel ?? '')) ?: (string) ($localPhone['label'] ?? '');
    $variant = $variant ?? 'hero';
    // Generic pages render the block collapsed; the utm_city script reveals it
    // once it knows which city the visitor came from.
    $alwaysVisible = $alwaysVisible ?? true;
@endphp
<div
  class="area-phones area-phones--{{ $variant }}"
  data-area-phones
  @unless($alwaysVisible) hidden @endunless
>
  <a
    href="tel:{{ site_phone_tel() }}"
    class="area-phones__item"
    data-phone-source="{{ $variant }}-phone-general"
  >
    <span class="area-phones__label">Bay Area</span>
    <span class="area-phones__number">{{ site_phone_display() }}</span>
  </a>
  <a
    href="{{ $localPhone ? 'tel:'.$localPhone['phone_tel'] : '#' }}"
    class="area-phones__item area-phones__item--local"
    data-phone-source="{{ $variant }}-phone-local"
    data-area-phones-local
    @unless($localPhone) hidden @endunless
  >
    <span class="area-phones__label" data-area-phones-local-label>{{ $localLabel }}</span>
    <span class="area-phones__number" data-area-phones-local-number>{{ $localPhone['phone_display'] ?? '' }}</span>
  </a>
</div>

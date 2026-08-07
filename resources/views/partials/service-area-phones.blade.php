@php
    $localPhone = $localPhone ?? null;
    $localLabel = trim((string) ($localLabel ?? '')) ?: (string) ($localPhone['name'] ?? '');
    // Generic pages render the block collapsed; the utm_city script reveals it
    // once it knows which city the visitor came from. Desktop only — on mobile
    // the hero promo swaps its single number instead.
    $alwaysVisible = $alwaysVisible ?? true;
@endphp
<div class="area-phones" data-area-phones @unless($alwaysVisible) hidden @endunless>
  <a
    href="tel:{{ site_phone_tel() }}"
    class="area-phones__item"
    data-phone-source="hero-phone-general"
    aria-label="Call {{ site_phone_display() }}"
  >
    <span class="area-phones__label">Bay Area</span>
    <span class="area-phones__plaque">
      <span class="area-phones__number">{{ site_phone_display() }}</span>
      <span class="area-phones__icon" aria-hidden="true">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
          <path d="M6.6 10.8c1.4 2.8 3.8 5.1 6.6 6.6l2.2-2.2c.3-.3.7-.4 1.1-.2 1.2.4 2.5.6 3.8.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1C10.6 21 3 13.4 3 4c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.6.6 3.8.1.4 0 .8-.3 1.1l-2.2 2.2Z"/>
        </svg>
      </span>
    </span>
  </a>
  <a
    href="{{ $localPhone ? 'tel:'.$localPhone['phone_tel'] : '#' }}"
    class="area-phones__item area-phones__item--local"
    data-phone-source="hero-phone-local"
    data-area-phones-local
    aria-label="Call {{ $localPhone['phone_display'] ?? '' }}"
    @unless($localPhone) hidden @endunless
  >
    <span class="area-phones__label" data-area-phones-local-label>{{ $localLabel }}</span>
    <span class="area-phones__plaque">
      <span class="area-phones__number" data-area-phones-local-number>{{ $localPhone['phone_display'] ?? '' }}</span>
      <span class="area-phones__icon" aria-hidden="true">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
          <path d="M6.6 10.8c1.4 2.8 3.8 5.1 6.6 6.6l2.2-2.2c.3-.3.7-.4 1.1-.2 1.2.4 2.5.6 3.8.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1C10.6 21 3 13.4 3 4c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.6.6 3.8.1.4 0 .8-.3 1.1l-2.2 2.2Z"/>
        </svg>
      </span>
    </span>
  </a>
</div>

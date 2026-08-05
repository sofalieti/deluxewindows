@php
  $variant = $variant ?? 'percent';
  $badgeNote = $badgeNote ?? match (promotion_category()) {
    'doors' => 'OFF Doors',
    'windows' => 'OFF Windows',
    default => 'OFF Windows & Doors',
  };
  $badgeHtml = $badgeHtml ?? null;
  $buttonLabel = $buttonLabel ?? 'Request a Free Estimate';
  $showExpires = $showExpires ?? true;
  $percent = $percent ?? app(\App\Services\PromotionControlService::class)->globalDiscountPercent() . '%';
  // On a service area page we advertise that city's number instead of the
  // general one; the utm_city script swaps it in on every other page.
  $localPhone = $localPhone ?? null;
  $heroPhoneTel = $localPhone['phone_tel'] ?? site_phone_tel();
  $heroPhoneDisplay = $localPhone['phone_display'] ?? site_phone_display();
@endphp

{{-- promo-offer.css is served inside the layout CSS bundle (see layouts/classic.blade.php) --}}
{{-- Same wrapper structure on home (percent) and brand pages (price) so gaps match. --}}
<div class="hero-mobile-promo">
  <div class="hero-mobile-promo__badge">
    @if($variant === 'price' && $badgeHtml)
      {!! $badgeHtml !!}
    @else
      <div class="promo-price-tag promo-price-tag--percent">
        <div class="promo-price-tag-line promo-price-tag-line--new">
          <span class="promo-price-tag-new">{{ $percent }}</span>
        </div>
        <div class="promo-price-tag-note">{{ $badgeNote }}</div>
        @if($showExpires)
          <div class="hero-mobile-promo__expires">Offer ends {{ promotion_date('us-short-no-year') }}</div>
        @endif
      </div>
    @endif
  </div>
  <button type="button" class="hero-mobile-promo__btn" data-open-estimate-modal>{{ $buttonLabel }}</button>
  <a
    href="tel:{{ $heroPhoneTel }}"
    class="hero-mobile-promo__phone"
    aria-label="Call {{ $heroPhoneDisplay }}"
    data-area-phone
  >
    <span class="hero-mobile-promo__phone-number" data-area-phone-number>{{ $heroPhoneDisplay }}</span>
    <span class="hero-mobile-promo__phone-icon" aria-hidden="true">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
        <path d="M6.6 10.8c1.4 2.8 3.8 5.1 6.6 6.6l2.2-2.2c.3-.3.7-.4 1.1-.2 1.2.4 2.5.6 3.8.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1C10.6 21 3 13.4 3 4c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.6.6 3.8.1.4 0 .8-.3 1.1l-2.2 2.2Z"/>
      </svg>
    </span>
  </a>
  <p class="hero-mobile-promo__owned">We are – 100% employee owned &amp; over 30 years in business!</p>
</div>

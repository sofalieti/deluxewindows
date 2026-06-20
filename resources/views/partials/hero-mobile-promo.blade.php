@php
  $variant = $variant ?? 'percent';
  $badgeNote = $badgeNote ?? 'OFF Windows';
  $badgeCopy = $badgeCopy ?? null;
  $buttonLabel = $buttonLabel ?? 'Request a Free Estimate';
  $showExpires = $showExpires ?? true;
  $percent = $percent ?? app(\App\Services\PromotionControlService::class)->globalDiscountPercent() . '%';
@endphp

@once
  @php
    $promoOfferCssPath = public_path('webflow-assets/css/promo-offer.css');
    $promoOfferCssVersion = file_exists($promoOfferCssPath) ? (string) filemtime($promoOfferCssPath) : '1';
  @endphp
  <link href="/webflow-assets/css/promo-offer.css?v={{ $promoOfferCssVersion }}" rel="stylesheet" type="text/css" />
@endonce

<div class="hero-mobile-promo">
  @if($variant === 'copy' && $badgeCopy)
    <div class="hero-mobile-promo__badge promo-price-tag hero-mobile-promo__badge--copy">
      <div class="hero-mobile-promo__badge-copy">{{ $badgeCopy }}</div>
    </div>
  @else
    <div class="hero-mobile-promo__badge promo-price-tag promo-price-tag--percent">
      <div class="promo-price-tag-line promo-price-tag-line--new">
        <span class="promo-price-tag-new">{{ $percent }}</span>
      </div>
      <div class="promo-price-tag-note">{{ $badgeNote }}</div>
      @if($showExpires)
        <div class="hero-mobile-promo__expires">Offer ends {{ promotion_date('us-short-no-year') }}</div>
      @endif
    </div>
  @endif
  <button type="button" class="hero-mobile-promo__btn" data-open-estimate-modal>{{ $buttonLabel }}</button>
</div>

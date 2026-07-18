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
@endphp

{{-- promo-offer.css is served inside the layout CSS bundle (see layouts/classic.blade.php) --}}
<div class="hero-mobile-promo">
  @if($variant === 'price' && $badgeHtml)
    <div class="hero-mobile-promo__badge">
      {!! $badgeHtml !!}
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

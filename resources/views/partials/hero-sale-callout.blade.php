@php
  $saleName = rtrim(trim(promotion_name()), '.');
  $salePercent = app(\App\Services\PromotionControlService::class)->globalDiscountPercent() . '%';
  $saleExpires = promotion_date('us-short');
@endphp
<div class="hero-sale-callout" aria-label="Current promotion">
  <span class="hero-sale-callout__name">{{ $saleName }}</span>
  <span class="hero-sale-callout__off">{{ $salePercent }} <span class="hero-sale-callout__off-label">OFF*</span></span>
  <span class="hero-sale-callout__expires">Offer Expires {{ $saleExpires }}</span>
</div>

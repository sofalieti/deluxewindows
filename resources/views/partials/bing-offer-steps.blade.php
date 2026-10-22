@php
  $bingPercent = bing_offer_percent();
  $bingEnds = bing_offer_end_date();
  $bingUnits = bing_offer_units();
  $bingSavings = bing_offer_savings(
    $bingOfferPricing ?? $windowPromotionPricing ?? $doorPromotionPricing ?? $brandPromotionPricing ?? null
  );
@endphp
@if($bingPercent > 0)
<section class="bing-only bing-offer-steps" aria-labelledby="bing-offer-steps-title">
  <div class="w-layout-blockcontainer container-default w-container">
    <div class="bing-offer-steps__inner">
      <div class="bing-offer-steps__intro">
        <h2 id="bing-offer-steps-title" class="bing-offer-steps__title">
          Replacing 3+ {{ $bingUnits }}? Get <span>{{ $bingPercent }}% OFF</span> the whole project.
        </h2>
        @if($bingSavings)
          <p class="bing-offer-steps__example">
            {{ $bingSavings['units'] }} {{ \Illuminate\Support\Str::of($bingUnits)->before(' or') }} on this page:
            <s>${{ number_format($bingSavings['base'] * $bingSavings['units']) }}</s>
            <strong>${{ number_format($bingSavings['final'] * $bingSavings['units']) }}</strong>
            &middot; you keep ${{ number_format($bingSavings['save']) }}
          </p>
        @endif
      </div>
      <ol class="bing-offer-steps__list">
        <li class="bing-offer-steps__item">
          <span class="bing-offer-steps__num" aria-hidden="true">1</span>
          <span><strong>Choose 3 or more</strong> {{ $bingUnits }} on the form</span>
        </li>
        <li class="bing-offer-steps__item">
          <span class="bing-offer-steps__num" aria-hidden="true">2</span>
          <span><strong>Free in-home measure</strong> and a written price</span>
        </li>
        <li class="bing-offer-steps__item">
          <span class="bing-offer-steps__num" aria-hidden="true">3</span>
          <span><strong>{{ $bingPercent }}% OFF installed</strong>@if($bingEnds !== '') &middot; ends {{ $bingEnds }}@endif</span>
        </li>
      </ol>
      <button type="button" class="bing-offer-steps__cta" data-open-estimate-modal>
        Get my {{ $bingPercent }}% OFF quote
      </button>
    </div>
  </div>
</section>
@endif

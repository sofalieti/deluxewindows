@php
  $bingPercent = bing_offer_percent();
  $bingEnds = bing_offer_end_date();
  $bingUnits = bing_offer_units();
  $bingSavings = bing_offer_savings($bingOfferPricing ?? null);
@endphp
@if($bingPercent > 0)
<div class="bing-only bing-hero-offer" aria-label="Project discount">
  <p class="bing-hero-offer__headline">
    <span class="bing-hero-offer__pct">{{ $bingPercent }}% OFF</span>
    <span class="bing-hero-offer__rule">when you replace 3 or more {{ $bingUnits }}</span>
  </p>
  <ul class="bing-hero-offer__points">
    <li>Discount applies to the whole project</li>
    <li>Free in-home measure &amp; written quote</li>
    @if($bingEnds !== '')
      <li>Offer ends {{ $bingEnds }}</li>
    @endif
  </ul>
  @if($bingSavings)
    <p class="bing-hero-offer__example">
      Example: {{ $bingSavings['units'] }} {{ \Illuminate\Support\Str::of($bingUnits)->before(' or') }} at
      <s>${{ number_format($bingSavings['base']) }}</s> <strong>${{ number_format($bingSavings['final']) }}</strong> each &mdash;
      you save <strong>${{ number_format($bingSavings['save']) }}</strong>
    </p>
  @endif
</div>
@endif

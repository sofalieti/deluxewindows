<label for="email-banner" class="body-14 hero-form-offer-footer">
  <span class="hero-form-offer-footer__expires">
    <em class="italic-text">Offer Expires</em>
    <span class="hero-form-offer-footer__date date-span">{{ promotion_date('us-short') }}</span>
  </span>
  <span class="hero-form-offer-footer__note bing-hide">Best for projects with 4+ windows</span>
  <span class="hero-form-offer-footer__note bing-only">{{ bing_offer_percent() }}% OFF applies to projects with 3+ {{ bing_offer_units() }}</span>
</label>

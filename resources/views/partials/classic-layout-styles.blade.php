    @php
      $promoOfferCssPath = public_path('webflow-assets/css/promo-offer.css');
      $promoOfferCssVersion = file_exists($promoOfferCssPath) ? (string) filemtime($promoOfferCssPath) : '1';
      $siteCustomCssPath = public_path('webflow-overrides/site-custom.css');
      $siteCustomCssVersion = file_exists($siteCustomCssPath) ? (string) filemtime($siteCustomCssPath) : '1';
    @endphp
    <link href="/webflow-assets/css/promo-offer.css?v={{ $promoOfferCssVersion }}" rel="stylesheet" type="text/css" />
    <link href="/webflow-overrides/site-custom.css?v={{ $siteCustomCssVersion }}" rel="stylesheet" type="text/css" />
    @include('partials.google-tags')

@php
  $yelpPayload = $yelpPayload ?? app(\App\Services\YelpReviewsService::class)->payload();
@endphp
@if($yelpPayload)
@php
  $yelpBusiness = $yelpPayload['business'];
  $yelpReviews = $yelpPayload['reviews'];
  $yelpCssPath = public_path('webflow-overrides/yelp-reviews.css');
  $yelpCssVersion = is_file($yelpCssPath) ? (string) filemtime($yelpCssPath) : '1';
  $yelpJsPath = public_path('webflow-overrides/yelp-reviews.js');
  $yelpJsVersion = is_file($yelpJsPath) ? (string) filemtime($yelpJsPath) : '1';
  $overallFill = max(0, min(100, ((float) $yelpBusiness['rating'] / 5) * 100));
@endphp
@once('dw-yelp-reviews-css')
    <link href="/webflow-overrides/yelp-reviews.css?v={{ $yelpCssVersion }}" rel="stylesheet" type="text/css" />
@endonce

      <div class="trust-badges-bar" id="trustBadgesBar">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="w-layout-grid grid grid-543">
            <div class="dw-yelp-badge-slot">
              <button
                type="button"
                class="dw-yelp-badge"
                data-yelp-badge
                aria-controls="dw-yelp-drawer"
                aria-expanded="false"
                aria-haspopup="dialog"
                aria-label="Open Yelp reviews, {{ $yelpBusiness['rating_label'] }} out of 5 from {{ $yelpBusiness['reviews_label'] }} reviews"
              >
                <span class="dw-yelp-badge__rating">{{ $yelpBusiness['rating_label'] }}</span>
                <span class="dw-yelp-badge__brand">Yelp</span>
                <span class="dw-yelp-stars dw-yelp-stars--sm dw-yelp-stars--on-dark" style="--dw-yelp-fill: {{ $overallFill }}%;" aria-hidden="true">
                  <span class="dw-yelp-stars__base"></span>
                  <span class="dw-yelp-stars__fill"></span>
                </span>
              </button>
            </div>
            <div class="div-block-65">
              <div class="text-block-46" aria-hidden="true">&#10003;</div>
              <div class="text-block-47">AAMA Certified Installers<br /></div>
            </div>
            <div class="div-block-65">
              <div class="text-block-46" aria-hidden="true">&#10003;</div>
              <div class="text-block-47">Financing Available<br /></div>
            </div>
            <div class="div-block-65">
              <div class="text-block-46" aria-hidden="true">&#10003;</div>
              <div class="text-block-47">40% Off — Limited Time</div>
            </div>
          </div>
        </div>
      </div>

      <div class="dw-yelp-drawer__backdrop" data-yelp-drawer-backdrop></div>
      <aside
        id="dw-yelp-drawer"
        class="dw-yelp-drawer"
        data-yelp-drawer
        data-yelp-reviews
        data-initial="6"
        role="dialog"
        aria-modal="true"
        aria-hidden="true"
        aria-labelledby="dw-yelp-drawer-title"
        tabindex="-1"
      >
        <div class="dw-yelp-drawer__header">
          <div>
            <p id="dw-yelp-drawer-title" class="dw-yelp-drawer__title">Yelp reviews</p>
            <p class="dw-yelp-drawer__score">
              <span class="dw-yelp-stars dw-yelp-stars--sm" style="--dw-yelp-fill: {{ $overallFill }}%;" aria-hidden="true">
                <span class="dw-yelp-stars__base"></span>
                <span class="dw-yelp-stars__fill"></span>
              </span>
              <strong>{{ $yelpBusiness['rating_label'] }}</strong>
              <span>{{ $yelpBusiness['reviews_label'] }} reviews</span>
            </p>
          </div>
          <button type="button" class="dw-yelp-drawer__close" data-yelp-drawer-close aria-label="Close reviews">
            &times;
          </button>
        </div>
        <ol class="dw-yelp__list dw-yelp-drawer__list">
          @foreach($yelpReviews as $review)
            @include('partials.yelp-review-item')
          @endforeach
        </ol>
        <button type="button" class="dw-yelp__more" data-yelp-more hidden>More reviews</button>
        <a class="dw-yelp-drawer__yelp-link" href="{{ $yelpBusiness['yelp_url'] }}" target="_blank" rel="noopener noreferrer">
          Read all reviews on Yelp
        </a>
      </aside>
@once('dw-yelp-reviews-js')
    <script src="/webflow-overrides/yelp-reviews.js?v={{ $yelpJsVersion }}" defer></script>
@endonce
@else
      <div class="trust-badges-bar" id="trustBadgesBar">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="w-layout-grid grid grid-543">
            <div></div>
            <div class="div-block-65">
              <div class="text-block-46" aria-hidden="true">&#10003;</div>
              <div class="text-block-47">AAMA Certified Installers<br /></div>
            </div>
            <div class="div-block-65">
              <div class="text-block-46" aria-hidden="true">&#10003;</div>
              <div class="text-block-47">Financing Available<br /></div>
            </div>
            <div class="div-block-65">
              <div class="text-block-46" aria-hidden="true">&#10003;</div>
              <div class="text-block-47">40% Off — Limited Time</div>
            </div>
          </div>
        </div>
      </div>
@endif

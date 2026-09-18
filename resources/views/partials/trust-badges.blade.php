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
@once
    <link href="/webflow-overrides/yelp-reviews.css?v={{ $yelpCssVersion }}" rel="stylesheet" type="text/css" />
@endonce

      <div
        data-animation="default"
        data-collapse="tiny"
        data-duration="400"
        data-easing="ease"
        data-easing2="ease"
        role="banner"
        class="navbar w-nav trust-badges-bar"
        id="trustBadgesBar"
      >
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="w-layout-grid grid grid-543">
            <div>
              <button
                type="button"
                class="dw-yelp-badge"
                data-yelp-badge
                aria-controls="dw-yelp-drawer"
                aria-expanded="false"
              >
                <svg class="dw-yelp-badge__burst" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                  <path fill="currentColor" d="M13.7 1.4c-.5-1.5-2.9-1.5-3.4 0L8.8 6.1 3.8 5.4c-1.6-.2-2.4 1.8-1.1 2.9l4 3.3-1.8 4.8c-.6 1.5 1.2 2.8 2.5 1.8L12 15.4l4.6 2.8c1.3 1 3.1-.3 2.5-1.8l-1.8-4.8 4-3.3c1.3-1.1.5-3.1-1.1-2.9l-5 .7-1.5-4.7z"/>
                </svg>
                <span class="dw-yelp-badge__copy">
                  <span class="dw-yelp-badge__row">
                    <span class="dw-yelp-stars dw-yelp-stars--sm dw-yelp-stars--on-dark" style="--dw-yelp-fill: {{ $overallFill }}%;" aria-hidden="true">
                      <span class="dw-yelp-stars__base"></span>
                      <span class="dw-yelp-stars__fill"></span>
                    </span>
                    <span class="dw-yelp-badge__rating">{{ $yelpBusiness['rating_label'] }}</span>
                  </span>
                  <span class="dw-yelp-badge__count">{{ $yelpBusiness['reviews_label'] }} Yelp reviews</span>
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
        <div class="w-nav-overlay" data-wf-ignore="" id="w-nav-overlay-3"></div>
      </div>

      <div class="dw-yelp-drawer__backdrop" data-yelp-drawer-backdrop hidden></div>
      <aside
        id="dw-yelp-drawer"
        class="dw-yelp-drawer"
        data-yelp-drawer
        data-yelp-reviews
        data-initial="6"
        hidden
        aria-hidden="true"
        aria-labelledby="dw-yelp-drawer-title"
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
@push('scripts')
@once
    <script src="/webflow-overrides/yelp-reviews.js?v={{ $yelpJsVersion }}" defer></script>
@endonce
@endpush
@else
      <div
        data-animation="default"
        data-collapse="tiny"
        data-duration="400"
        data-easing="ease"
        data-easing2="ease"
        role="banner"
        class="navbar w-nav trust-badges-bar"
        id="trustBadgesBar"
      >
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
        <div class="w-nav-overlay" data-wf-ignore="" id="w-nav-overlay-3"></div>
      </div>
@endif

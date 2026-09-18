@php
  $yelpPayload = $yelpPayload ?? app(\App\Services\YelpReviewsService::class)->payload();
  $yelpShowHeading = $yelpShowHeading ?? true;
  $yelpHeading = $yelpHeading ?? 'Reviews';
  $yelpInitialCount = (int) ($yelpInitialCount ?? 6);
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

      <section class="section-122 dw-yelp" data-yelp-reviews data-initial="{{ $yelpInitialCount }}" aria-labelledby="dw-yelp-heading">
        <div class="w-layout-blockcontainer container-default w-container">
          @if($yelpShowHeading)
            <h2 id="dw-yelp-heading" class="heading-5 dw-yelp__heading">{{ $yelpHeading }}</h2>
          @else
            <h2 id="dw-yelp-heading" class="visually-hidden">Yelp reviews</h2>
          @endif

          <div class="dw-yelp__scorecard">
            <a
              class="dw-yelp__brand"
              href="{{ $yelpBusiness['yelp_url'] }}"
              target="_blank"
              rel="noopener noreferrer"
            >
              <svg class="dw-yelp__burst" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                <path fill="currentColor" d="M13.7 1.4c-.5-1.5-2.9-1.5-3.4 0L8.8 6.1 3.8 5.4c-1.6-.2-2.4 1.8-1.1 2.9l4 3.3-1.8 4.8c-.6 1.5 1.2 2.8 2.5 1.8L12 15.4l4.6 2.8c1.3 1 3.1-.3 2.5-1.8l-1.8-4.8 4-3.3c1.3-1.1.5-3.1-1.1-2.9l-5 .7-1.5-4.7z"/>
              </svg>
              Yelp
            </a>
            <div class="dw-yelp__score">
              <div class="dw-yelp__rating-number">{{ $yelpBusiness['rating_label'] }}</div>
              <div class="dw-yelp__rating-meta">
                <span class="dw-yelp-stars" style="--dw-yelp-fill: {{ $overallFill }}%;" aria-label="{{ $yelpBusiness['rating_label'] }} out of 5 stars">
                  <span class="dw-yelp-stars__base" aria-hidden="true"></span>
                  <span class="dw-yelp-stars__fill" aria-hidden="true"></span>
                </span>
                <p class="dw-yelp__count">
                  {{ $yelpBusiness['reviews_label'] }} Yelp reviews
                  ·
                  <a href="{{ $yelpBusiness['yelp_url'] }}" target="_blank" rel="noopener noreferrer">Read on Yelp</a>
                </p>
              </div>
            </div>
          </div>

          <ol class="dw-yelp__list">
            @foreach($yelpReviews as $review)
              @php $reviewFill = max(0, min(100, ($review['rating'] / 5) * 100)); @endphp
              <li class="dw-yelp__review" data-yelp-review>
                <div class="dw-yelp__review-top">
                  <div class="dw-yelp__avatar">
                    @if($review['photo_url'])
                      <img src="{{ $review['photo_url'] }}" alt="" width="48" height="48" loading="lazy" />
                    @else
                      {{ $review['initials'] }}
                    @endif
                  </div>
                  <div class="dw-yelp__author">
                    <p class="dw-yelp__name">{{ $review['author'] }}</p>
                    @if($review['published_label'] !== '')
                      <p class="dw-yelp__date">
                        <time datetime="{{ $review['published_iso'] }}">{{ $review['published_label'] }}</time>
                      </p>
                    @endif
                  </div>
                </div>
                <div class="dw-yelp__review-rating">
                  <span class="dw-yelp-stars dw-yelp-stars--sm" style="--dw-yelp-fill: {{ $reviewFill }}%;" aria-label="{{ $review['rating'] }} out of 5 stars">
                    <span class="dw-yelp-stars__base" aria-hidden="true"></span>
                    <span class="dw-yelp-stars__fill" aria-hidden="true"></span>
                  </span>
                </div>
                <p class="dw-yelp__text is-clamped" data-yelp-text>{{ $review['text'] }}</p>
                <button type="button" class="dw-yelp__read-more" data-yelp-read-more>Read more</button>
                @if($review['review_photos'] !== [])
                  <div class="dw-yelp__photos">
                    @foreach($review['review_photos'] as $photoUrl)
                      <a href="{{ $review['yelp_review_url'] !== '' ? $review['yelp_review_url'] : $photoUrl }}" target="_blank" rel="noopener noreferrer">
                        <img src="{{ $photoUrl }}" alt="" loading="lazy" />
                      </a>
                    @endforeach
                  </div>
                @endif
                @if($review['yelp_review_url'] !== '')
                  <a class="dw-yelp__review-link" href="{{ $review['yelp_review_url'] }}" target="_blank" rel="noopener noreferrer">View on Yelp</a>
                @endif
              </li>
            @endforeach
          </ol>

          <button type="button" class="dw-yelp__more" data-yelp-more hidden>More reviews</button>
          <p class="dw-yelp__footnote">
            Overall rating {{ $yelpBusiness['rating_label'] }} from {{ $yelpBusiness['reviews_label'] }} Yelp reviews.
            Individual reviews below are from
            <a href="{{ $yelpBusiness['yelp_url'] }}" target="_blank" rel="noopener noreferrer">Deluxe Windows on Yelp</a>.
          </p>
        </div>
      </section>
@push('scripts')
@once
    <script src="/webflow-overrides/yelp-reviews.js?v={{ $yelpJsVersion }}" defer></script>
@endonce
@endpush
@endif

              @php $reviewFill = max(0, min(100, ($review['rating'] / 5) * 100)); @endphp
              <li class="dw-yelp__review is-collapsed" data-yelp-review>
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
                <button type="button" class="dw-yelp__read-more" data-yelp-read-more hidden>Show more</button>
                @if($review['review_photos'] !== [])
                  <div class="dw-yelp__photos" data-yelp-photos>
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

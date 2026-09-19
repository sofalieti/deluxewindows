              @php
                $reviewFill = max(0, min(100, ($review['rating'] / 5) * 100));
                $reviewUrl = (string) ($review['yelp_review_url'] ?? '');
                $personTag = $reviewUrl !== '' ? 'a' : 'div';
              @endphp
              <li class="dw-yelp__review is-collapsed" data-yelp-review>
                <{{ $personTag }}
                  class="dw-yelp__person"
                  @if($reviewUrl !== '')
                    href="{{ $reviewUrl }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    aria-label="{{ $review['author'] }} on Yelp"
                  @endif
                >
                  <span class="dw-yelp__avatar">
                    @if($review['photo_url'])
                      <img src="{{ $review['photo_url'] }}" alt="" width="36" height="36" loading="lazy" />
                    @else
                      {{ $review['initials'] }}
                    @endif
                  </span>
                  <span class="dw-yelp__author">
                    <span class="dw-yelp__name">{{ $review['author'] }}</span>
                    <span class="dw-yelp-stars dw-yelp-stars--xs" style="--dw-yelp-fill: {{ $reviewFill }}%;" aria-label="{{ $review['rating'] }} out of 5 stars">
                      <span class="dw-yelp-stars__base" aria-hidden="true"></span>
                      <span class="dw-yelp-stars__fill" aria-hidden="true"></span>
                    </span>
                    @if($review['published_label'] !== '')
                      <time class="dw-yelp__date" datetime="{{ $review['published_iso'] }}">{{ $review['published_label'] }}</time>
                    @endif
                  </span>
                </{{ $personTag }}>
                <p class="dw-yelp__text is-clamped" data-yelp-text>{{ $review['text'] }}</p>
                <button type="button" class="dw-yelp__read-more" data-yelp-read-more hidden>Show more</button>
                @if($review['review_photos'] !== [])
                  <div class="dw-yelp__photos" data-yelp-photos>
                    @foreach($review['review_photos'] as $photoUrl)
                      <a href="{{ $reviewUrl !== '' ? $reviewUrl : $photoUrl }}" target="_blank" rel="noopener noreferrer">
                        <img src="{{ $photoUrl }}" alt="" loading="lazy" />
                      </a>
                    @endforeach
                  </div>
                @endif
              </li>

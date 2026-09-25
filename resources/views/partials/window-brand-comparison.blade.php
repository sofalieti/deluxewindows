@php
  $currentSlug = $comparison['current_slug'];
  $peerSlugs = $comparison['peer_slugs'];
  $brands = $comparison['brands'];
  $slots = [
      'current' => $comparison['current'],
      'peer-1' => $brands[$peerSlugs[0]],
      'peer-2' => $brands[$peerSlugs[1]],
  ];
  $comparisonJson = json_encode(
      ['brands' => $brands],
      JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR
  );
@endphp

<section
  class="wbc"
  data-window-brand-comparison
  aria-labelledby="window-brand-comparison-heading"
>
  <div class="wbc__intro">
    <div>
      <p class="wbc__eyebrow">Window brand guide</p>
      <h2 id="window-brand-comparison-heading" class="heading-20">Compare Window Brands</h2>
      <p class="wbc__lead">
        Compare {{ $comparison['current']['name'] }} with other brands carried by Deluxe Windows.
        Choose alternatives that match your project priorities.
      </p>
    </div>
    <button type="button" class="primary-button w-inline-block wbc__intro-cta" data-open-estimate-modal>
      <span>Get Expert Advice</span>
    </button>
  </div>

  <div class="wbc__comparison" role="group" aria-label="Window brand comparison">
    <div class="wbc__headers">
      <div class="wbc__header-spacer" aria-hidden="true">Comparison criteria</div>
      @foreach($slots as $slot => $brand)
        <article
          class="wbc__brand-header{{ $slot === 'current' ? ' is-current' : '' }}"
          data-wbc-header
          data-slot="{{ $slot }}"
          @if($slot !== 'current') aria-live="polite" @endif
        >
          @if($slot === 'current')
            <span class="wbc__current-label">Current brand</span>
            <div class="wbc__name-row">
              <h3 class="wbc__brand-name" data-wbc-name>{{ $brand['name'] }}</h3>
            </div>
          @else
            <label class="wbc__select-label" for="wbc-{{ $slot }}-select">Compare with</label>
            <div class="wbc__name-row">
              <select
                id="wbc-{{ $slot }}-select"
                class="wbc__select wbc__brand-name"
                data-wbc-select
                data-slot="{{ $slot }}"
                aria-label="Choose window brand for comparison column {{ $loop->index }}"
              >
                @foreach($brands as $optionSlug => $option)
                  @continue($optionSlug === $currentSlug)
                  <option
                    value="{{ $optionSlug }}"
                    @selected($optionSlug === $peerSlugs[$slot === 'peer-1' ? 0 : 1])
                  >{{ $option['name'] }}</option>
                @endforeach
              </select>
            </div>
          @endif
          <p class="wbc__tagline" data-wbc-tagline>{{ $brand['tagline'] }}</p>
          <p class="wbc__best-for">
            <strong>Best for:</strong>
            <span data-wbc-best-for>{{ $brand['best_for'] }}</span>
          </p>
          <div class="wbc__header-links">
            <a data-wbc-link href="{{ $brand['url'] }}">View {{ $brand['name'] }}</a>
            <a
              data-wbc-source
              href="{{ $brand['sources'][0]['url'] }}"
              target="_blank"
              rel="noopener noreferrer"
            >Official information</a>
          </div>
        </article>
      @endforeach
    </div>

    <div class="wbc__expand">
      <button
        type="button"
        class="wbc__expand-btn"
        data-wbc-expand
        aria-expanded="false"
        aria-controls="wbc-criteria"
      >
        Compare all points
      </button>
    </div>

    <div class="wbc__criteria" id="wbc-criteria">
      @foreach($comparison['criteria'] as $criterion)
        <div
          class="wbc__criterion-row"
          data-wbc-criterion="{{ $criterion['key'] }}"
          data-type="{{ $criterion['type'] }}"
        >
          <div class="wbc__criterion-title">
            <strong>{{ $criterion['label'] }}</strong>
            <span>{{ $criterion['description'] }}</span>
          </div>
          @foreach($slots as $slot => $brand)
            @php $value = $brand['values'][$criterion['key']]; @endphp
            <div
              class="wbc__value{{ $criterion['type'] === 'text' ? ' is-text' : '' }}"
              data-wbc-value
              data-slot="{{ $slot }}"
              data-key="{{ $criterion['key'] }}"
            >
              @if($criterion['type'] === 'score')
                <div
                  class="wbc__rating"
                  data-wbc-rating
                  role="img"
                  aria-label="{{ $value['rating'] }} out of 5: {{ $value['label'] }}"
                >
                  @for($ratingIndex = 1; $ratingIndex <= 5; $ratingIndex++)
                    <span class="{{ $ratingIndex <= $value['rating'] ? 'is-filled' : '' }}"></span>
                  @endfor
                </div>
              @endif
              <strong class="wbc__value-label" data-wbc-value-label>{{ $value['label'] }}</strong>
              <p data-wbc-value-detail>{{ $value['detail'] }}</p>
            </div>
          @endforeach
        </div>
      @endforeach
    </div>
  </div>

  <div class="wbc__tradeoffs">
    @foreach($slots as $slot => $brand)
      <article
        class="wbc__tradeoff-card{{ $slot === 'current' ? ' is-current' : '' }}"
        data-wbc-tradeoffs
        data-slot="{{ $slot }}"
        @if($slot !== 'current') aria-live="polite" @endif
      >
        <h3><span data-wbc-tradeoff-name>{{ $brand['name'] }}</span>: Pros &amp; Considerations</h3>
        <a
          class="wbc__tradeoff-link"
          data-wbc-tradeoff-link
          href="{{ $brand['url'] }}"
        >View {{ $brand['name'] }} details</a>
        <div class="wbc__tradeoff-columns">
          <div>
            <h4 class="wbc__pros-heading">Pros</h4>
            <ul data-wbc-pros>
              @foreach($brand['pros'] as $pro)
                <li>{{ $pro }}</li>
              @endforeach
            </ul>
          </div>
          <div>
            <h4 class="wbc__considerations-heading">Considerations</h4>
            <ul data-wbc-considerations>
              @foreach($brand['considerations'] as $consideration)
                <li>{{ $consideration }}</li>
              @endforeach
            </ul>
          </div>
        </div>
      </article>
    @endforeach
  </div>

  <details class="wbc__footnote">
    <summary>Important comparison notes</summary>
    <p>{{ $comparison['disclaimer'] }}</p>
    <p>Research reviewed {{ $comparison['verified_at'] }}. Manufacturer documents and your written proposal control.</p>
  </details>

  <script type="application/json" data-wbc-data>{!! $comparisonJson !!}</script>
</section>

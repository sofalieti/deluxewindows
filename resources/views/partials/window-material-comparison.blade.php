@php
  $currentSlug = $comparison['current_slug'];
  $peerSlugs = $comparison['peer_slugs'];
  $materials = $comparison['materials'];
  $slots = [
      'current' => $comparison['current'],
      'peer-1' => $materials[$peerSlugs[0]],
      'peer-2' => $materials[$peerSlugs[1]],
  ];
  $comparisonJson = json_encode(
      ['materials' => $materials],
      JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR
  );
@endphp

<div
  class="wmc"
  data-window-material-comparison
  aria-labelledby="window-material-comparison-heading"
>
  <div class="wmc__intro">
    <div>
      <p class="wmc__eyebrow">Window frame guide</p>
      <h2 id="window-material-comparison-heading" class="heading-20">
        Compare Window Materials
      </h2>
      <p class="wmc__lead">
        See how {{ $comparison['current']['short_name'] }} compares with other frame materials.
        Change either alternative to match the options on your shortlist.
      </p>
    </div>
    <a href="#contact" class="primary-button w-inline-block wmc__intro-cta">
      <span>Get Expert Advice</span>
    </a>
  </div>

  <div class="wmc__comparison" role="group" aria-label="Window material comparison">
    <div class="wmc__headers">
      <div class="wmc__header-spacer" aria-hidden="true">Comparison criteria</div>
      @foreach($slots as $slot => $material)
        <article
          class="wmc__material-header{{ $slot === 'current' ? ' is-current' : '' }}"
          data-wmc-header
          data-slot="{{ $slot }}"
          @if($slot !== 'current') aria-live="polite" @endif
        >
          @if($slot === 'current')
            <span class="wmc__current-label">Your current choice</span>
            <h3 class="wmc__material-name" data-wmc-name>{{ $material['short_name'] }}</h3>
          @else
            <label class="wmc__select-label" for="wmc-{{ $slot }}-select">
              Compare with
            </label>
            <select
              id="wmc-{{ $slot }}-select"
              class="wmc__select"
              data-wmc-select
              data-slot="{{ $slot }}"
              aria-label="Choose material for comparison column {{ $loop->index }}"
            >
              @foreach($materials as $optionSlug => $option)
                @continue($optionSlug === $currentSlug)
                <option
                  value="{{ $optionSlug }}"
                  @selected($optionSlug === $peerSlugs[$slot === 'peer-1' ? 0 : 1])
                >{{ $option['short_name'] }}</option>
              @endforeach
            </select>
          @endif
          <p class="wmc__tagline" data-wmc-tagline>{{ $material['tagline'] }}</p>
          <p class="wmc__best-for">
            <strong>Best for:</strong>
            <span data-wmc-best-for>{{ $material['best_for'] }}</span>
          </p>
          <a class="wmc__details-link" data-wmc-link href="{{ $material['url'] }}">
            View {{ $material['short_name'] }} details
          </a>
        </article>
      @endforeach
    </div>

    <div class="wmc__criteria">
      @foreach($comparison['criteria'] as $criterion)
        <div class="wmc__criterion-row" data-wmc-criterion="{{ $criterion['key'] }}">
          <div class="wmc__criterion-title">
            <strong>{{ $criterion['label'] }}</strong>
            <span>{{ $criterion['description'] }}</span>
          </div>
          @foreach($slots as $slot => $material)
            @php $value = $material['values'][$criterion['key']]; @endphp
            <div
              class="wmc__value"
              data-wmc-value
              data-slot="{{ $slot }}"
              data-key="{{ $criterion['key'] }}"
            >
              <span class="wmc__mobile-material" data-wmc-mobile-name>{{ $material['short_name'] }}</span>
              <div
                class="wmc__rating"
                data-wmc-rating
                role="img"
                aria-label="{{ $value['rating'] }} out of 5: {{ $value['label'] }}"
              >
                @for($ratingIndex = 1; $ratingIndex <= 5; $ratingIndex++)
                  <span class="{{ $ratingIndex <= $value['rating'] ? 'is-filled' : '' }}"></span>
                @endfor
              </div>
              <strong class="wmc__value-label" data-wmc-value-label>{{ $value['label'] }}</strong>
              <p data-wmc-value-detail>{{ $value['detail'] }}</p>
            </div>
          @endforeach
        </div>
      @endforeach
    </div>
  </div>

  <div class="wmc__tradeoffs">
    @foreach($slots as $slot => $material)
      <article
        class="wmc__tradeoff-card{{ $slot === 'current' ? ' is-current' : '' }}"
        data-wmc-tradeoffs
        data-slot="{{ $slot }}"
        @if($slot !== 'current') aria-live="polite" @endif
      >
        <h3><span data-wmc-tradeoff-name>{{ $material['short_name'] }}</span>: Pros &amp; Cons</h3>
        <div class="wmc__tradeoff-columns">
          <div>
            <h4 class="wmc__pros-heading">Pros</h4>
            <ul data-wmc-pros>
              @foreach($material['pros'] as $pro)
                <li>{{ $pro }}</li>
              @endforeach
            </ul>
          </div>
          <div>
            <h4 class="wmc__cons-heading">Considerations</h4>
            <ul data-wmc-cons>
              @foreach($material['cons'] as $con)
                <li>{{ $con }}</li>
              @endforeach
            </ul>
          </div>
        </div>
      </article>
    @endforeach
  </div>

  <div class="wmc__footnote">
    <p>{{ $comparison['disclaimer'] }}</p>
    <details>
      <summary>How we compare materials</summary>
      <p>
        Ratings are relative guides, not product certifications. Higher ratings indicate a
        more favorable material characteristic. Review the NFRC label for exact whole-window
        performance.
      </p>
      <ul>
        @foreach($comparison['sources'] as $source)
          <li>
            <a href="{{ $source['url'] }}" target="_blank" rel="noopener noreferrer">
              {{ $source['label'] }}
            </a>
          </li>
        @endforeach
      </ul>
    </details>
  </div>

  <script type="application/json" data-wmc-data>{!! $comparisonJson !!}</script>
</div>

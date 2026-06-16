    <section class="section_breadcrumbs section-121">
      <div class="w-layout-blockcontainer container-default breadcrumbs-container w-container">
        <div class="breadcrumbs-wrapper">
          <a href="/" class="breadcrumb-link">Home</a>
          <div class="breadcrumb-div">/</div>
          @if($countyHubSlug)
          <a href="/county-hub-pages/{{ $countyHubSlug }}" class="breadcrumb-link hidden-link">SERVICE AREAS</a>
          @else
          <a href="#" class="breadcrumb-link hidden-link">SERVICE AREAS</a>
          @endif
          <div class="breadcrumb-div hidden-txt">/</div>
          <a href="#" class="breadcrumb-link hidden-link">{{ $cityName }}</a>
        </div>
      </div>
    </section>

    <section class="section-main">
      <div class="w-layout-blockcontainer container-default w-container">
        <div class="w-layout-grid grid-545">
          <div class="div-block-63">
            <div class="code-embed-9 w-embed">
              <h2 class="display-8 mid types">Window Replacement Services in {{ $cityName }}, {{ $countyName }}</h2>
            </div>
            @if($paragraph1)
            <div class="rich-text-block-11 w-richtext">
              {!! $paragraph1 !!}
            </div>
            @endif
            @if($paragraph2)
            <div class="rich-text-block-11 border-bottom stat-row w-richtext">
              {!! $paragraph2 !!}
            </div>
            @endif
          </div>
          <div>
            <div class="div-block-62 asdf">
              <div class="code-embed-11 dsafsdf w-embed">{{ $cityName }} Service Summary</div>
              <div class="code-embed-10 w-embed">Service Area    <span style="float:right;">{{ $cityLabel }}</span></div>
              <div class="code-embed-10 w-embed">County    <span style="float:right;">{{ $countyName }}</span></div>
              <div class="code-embed-10 w-embed">Starting Price    <span style="float:right; font-weight: bold; color:#e87722;">From $549/window</span></div>
              <div class="code-embed-10 w-embed">Current Offer    <span style="float:right; font-weight: bold; color:#e87722;">{{ promotion_percent_label() }}</span></div>
              <div class="code-embed-10 w-embed">Yelp Rating    <span style="float:right;">★★★★★ 4.9</span></div>
              <div class="code-embed-10 w-embed">Installation Time<span style="float:right;">1–2 Days</span></div>
              <div class="code-embed-10 lastcode w-embed">Estimates <span style="float:right; font-weight: bold; color:#e87722;">Free</span></div>
              <a href="/contacts" class="primary-button-2 custom w-inline-block"><div>Request a Free Estimate</div></a>
            </div>
          </div>
        </div>
      </div>
    </section>

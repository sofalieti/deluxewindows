@extends('layouts.classic')

@section('wfPage', '6841ddf8ace3d9d9facb156f')
@section('wfCollection', '6841ddf8ace3d9d9facb1588')
@section('wfItemSlug', $slug)
@section('htmlClass', '')
@section('bodyClass', 'body-18 height-auto door-detail-page')

@section('head')
    @php
      $doorDetailCssPath = public_path('webflow-overrides/door-detail.css');
      $doorDetailCssVersion = file_exists($doorDetailCssPath)
        ? (string) filemtime($doorDetailCssPath)
        : '1';
    @endphp
    <link href="/webflow-overrides/door-detail.css?v={{ $doorDetailCssVersion }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
      @include('partials.hero', [
        'doorHero' => true,
        'doorHeroImage' => $heroImage,
        'doorDiscountHtml' => $discountHtml,
        'brandHeroFormHtml' => $doorHeroFormHtml,
        'brandPromotionPricing' => $doorPromotionPricing,
      ])

      @include('partials.trust-badges')

      <section class="section_breadcrumbs section-121">
        <div class="w-layout-blockcontainer container-default breadcrumbs-container w-container">
          <div class="breadcrumbs-wrapper">
            <a href="/" class="breadcrumb-link">Home</a>
            <div class="breadcrumb-div">/</div>
            <a href="/doors" class="breadcrumb-link hidden-link">Doors</a>
            <div class="breadcrumb-div hidden-txt">/</div>
            <div class="breadcrumb-text">{{ $title }}</div>
          </div>
        </div>
      </section>

      <section class="section-5 pd-120px top-none mobile-content-section">
        <div class="w-layout-blockcontainer container-default-3 w-container">
          <div class="mg-top-extra-large-3">
            <div class="w-layout-grid grid-2-columns-4 listing-grid">
              <div class="inner-container _690px _100-tablet">
                <div>
                  <h1 class="display-38 mid">{{ $title }}</h1>
                  @if($summary)
                  <div class="mg-top-small-3">
                    <div class="text-block-43">{{ $summary }}</div>
                  </div>
                  @endif
                </div>
                <div class="divider mg-large"></div>
                @if($aboutHtml)
                <div>
                  <div class="mg-top-small-3">
                    <div class="rich-text-block w-richtext">
                      {!! $aboutHtml !!}
                    </div>
                  </div>
                </div>
                @endif
              </div>
            </div>
          </div>

          @php
            $allGalleryImages = collect();
            if ($mainImage) {
                $allGalleryImages->push($mainImage);
            }
            foreach ($galleryImages as $gi) {
                if (! $allGalleryImages->contains($gi)) {
                    $allGalleryImages->push($gi);
                }
            }
            $galleryMainUrl = function ($url) {
                try {
                    return thumbnail_url($url, 'gallery_main') ?: $url;
                } catch (\Throwable) {
                    return $url;
                }
            };
            $galleryThumbUrl = function ($url) {
                try {
                    return thumbnail_url($url, 'gallery_thumb') ?: $url;
                } catch (\Throwable) {
                    return $url;
                }
            };
          @endphp
          <div class="image-wrapper border-radius-image-default">
            <div class="dw-gallery" id="dw-gallery">
              <div class="dw-gallery__main">
                <img
                  id="dw-main-img"
                  src="{{ $allGalleryImages->isNotEmpty() ? $galleryMainUrl($allGalleryImages->first()) : '' }}"
                  alt="{{ $title }}"
                  loading="eager"
                  class="image cover-image _200px---mbp"
                />
              </div>
              @if($allGalleryImages->count() > 1)
              <div class="dw-gallery__row">
                <button class="dw-gallery__arrow" id="dw-prev" aria-label="Previous" disabled>
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                </button>
                <div class="dw-gallery__track-wrapper">
                  <div class="dw-gallery__track" id="dw-track">
                    @foreach($allGalleryImages as $idx => $img)
                    <button
                      class="dw-gallery__thumb{{ $idx === 0 ? ' is-active' : '' }}"
                      data-src="{{ $galleryMainUrl($img) }}"
                      data-idx="{{ $idx }}"
                      aria-label="Image {{ $idx + 1 }}"
                    ><img src="{{ $galleryThumbUrl($img) }}" alt="{{ $title }} {{ $idx + 1 }}" loading="lazy" class="image cover-image _120px---mbp" /></button>
                    @endforeach
                  </div>
                </div>
                <button class="dw-gallery__arrow" id="dw-next" aria-label="Next">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </button>
              </div>
              @endif
            </div>
          </div>
        </div>
      </section>

      @if($doorBrands->count() > 0)
      <section class="section top-none">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="title-left---content-right">
            <h2 class="heading-35">{{ $brandsTitle }}</h2>
          </div>
          <div class="mg-top-large">
            <div class="collection-list-wrapper-5 w-dyn-list">
              <div role="list" class="collection-list-2 door-brands-grid w-dyn-items">
                @foreach($doorBrands as $brand)
                <div role="listitem" class="w-dyn-item">
                  <a href="/brands/{{ $brand['slug'] }}" class="property-wrapper-v1 w-inline-block">
                    <div class="property-card-top-content-v1">
                      <div class="image-wrapper border-radius-image-default property-card-top-content-v1---image">
                        @if($brand['image'])
                        <img src="{{ $brand['image'] }}" loading="eager" alt="{{ $brand['name'] }}" class="image cover-image" />
                        @endif
                      </div>
                    </div>
                  </a>
                </div>
                @endforeach
              </div>
            </div>
          </div>
        </div>
      </section>
      @endif

      @if($learnMoreDoors->count() > 0)
      <section class="section pd-120px">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="text-center-mbp">
            <div class="title-left---content-right">
              <div class="width-100-mobile-portrait">
                <h2 class="heading-22">Learn More about <br />Different Door Types</h2>
              </div>
            </div>
          </div>
          <div class="mg-top-large">
            <div class="w-dyn-list">
              <div role="list" class="grid-3-columns blog-grid---v1---3-posts w-dyn-items">
                @foreach($learnMoreDoors as $ld)
                <div role="listitem" class="w-dyn-item">
                  <a
                    href="/doors/{{ $ld['slug'] }}"
                    class="blog-card-wrapper-v1 _3-posts---item w-inline-block{{ $ld['slug'] === $slug ? ' w--current' : '' }}"
                  >
                    <div class="blog-card-top-content-v1 _3-posts---item">
                      <div class="image-wrapper border-radius-image-default blog-card-top-content-v1---image">
                        @if($ld['image'])
                        <img src="{{ $ld['image'] }}" loading="eager" alt="{{ $ld['name'] }}" class="image cover-image" />
                        @endif
                      </div>
                      <div class="badge-wrapper---top-left"></div>
                    </div>
                    <div class="blog-card-bottom-content-v1 _3-posts---item">
                      <h3 class="display-5">{{ $ld['name'] }}</h3>
                    </div>
                  </a>
                </div>
                @endforeach
              </div>
            </div>
          </div>
          <div class="buttons-row">
            <a href="/doors" class="primary-button w-inline-block">
              <div class="text-block-22">All Door Options</div>
            </a>
          </div>
        </div>
      </section>
      @endif

      <section class="section hero-v4">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="inner-container _600px center">
            <div class="text-center">
              <div class="inner-container _500px---mbl center">
                <h1 class="display-9 mid">Upgrade to Energy Efficient Windows and Doors for Less</h1>
              </div>
            </div>
          </div>
          <div class="mg-top-48px">
            <div class="w-layout-grid grid-3-columns pricing-grid">
              <div class="card plan-card">
                <div class="product-card---top-content">
                  <div class="center-content">
                    <div class="image-wrapper plan-icon">
                      <img alt="" loading="eager" src="/webflow-assets/images/684d5abd999584226df187df_star_rate_24dp_E3E3E3_FILL0_wght400_GRAD0_opsz24.svg" class="image cover-image" />
                    </div>
                    <div class="mg-top-small mg-top-16px---mbl"><h2 class="display-5 mid">No FICO</h2></div>
                    <div class="mg-top-extra-small"><p>Your credit rating does not impact your ability to qualify.</p></div>
                  </div>
                </div>
                <div class="product-card---bottom-content">
                  <div class="mg-top-default"><div class="divider"></div></div>
                  <div class="div-block-44">
                    <div class="w-layout-grid grid-1-column gap-row-16px">
                      <div class="feature-wrapper dark v1"><div class="check-icon feature-plan" aria-hidden="true">&#xE82F;</div><div class="display-2">No Credit Score Required</div></div>
                      <div class="feature-wrapper dark v1"><div class="check-icon feature-plan" aria-hidden="true">&#xE82F;</div><div class="display-2">Fast &amp; Simple Approval</div></div>
                      <div class="feature-wrapper dark v1"><div class="check-icon feature-plan" aria-hidden="true">&#xE82F;</div><div class="display-2">Improve Home Value Now</div></div>
                      <div class="feature-wrapper dark v1"><div class="check-icon feature-plan" aria-hidden="true">&#xE82F;</div><div class="display-2">Second Chance Financing</div></div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="card plan-card">
                <div class="product-card---top-content">
                  <div class="center-content">
                    <div class="image-wrapper plan-icon">
                      <img alt="" loading="eager" src="/webflow-assets/images/684d5b02ab39a043513ad935_trending_down_24dp_E3E3E3_FILL0_wght400_GRAD0_opsz24.svg" class="image cover-image" />
                    </div>
                    <div class="mg-top-small mg-top-16px---mbl"><h2 class="display-5 mid">Lower Fixed Rates</h2></div>
                    <div class="mg-top-extra-small"><p>Payment remains the same for the life of your financing.</p></div>
                  </div>
                </div>
                <div class="product-card---bottom-content">
                  <div class="mg-top-default"><div class="divider"></div></div>
                  <div class="div-block-43">
                    <div class="w-layout-grid grid-1-column gap-row-16px">
                      <div class="feature-wrapper dark v1"><div class="check-icon feature-plan" aria-hidden="true">&#xE82F;</div><div class="display-2">Budget-Friendly Payments</div></div>
                      <div class="feature-wrapper dark v1"><div class="check-icon feature-plan" aria-hidden="true">&#xE82F;</div><div class="display-2">Long-Term Savings</div></div>
                      <div class="feature-wrapper dark v1"><div class="check-icon feature-plan" aria-hidden="true">&#xE82F;</div><div class="display-2">Secure &amp; Stable</div></div>
                      <div class="feature-wrapper dark v1"><div class="check-icon feature-plan" aria-hidden="true">&#xE82F;</div><div class="display-2">Immediate Upgrades</div></div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="card plan-card">
                <div class="product-card---top-content">
                  <div class="center-content">
                    <div class="image-wrapper plan-icon">
                      <img alt="" loading="eager" src="/webflow-assets/images/684d5b0fe6b11bd987eb461c_all_inclusive_24dp_E3E3E3_FILL0_wght400_GRAD0_opsz24.svg" class="image cover-image" />
                    </div>
                    <div class="mg-top-small mg-top-16px---mbl"><h2 class="display-5 mid">Longer Terms</h2></div>
                    <div class="mg-top-extra-small"><p>Flexible repayment terms - <br />up to 30 years for some projects.</p></div>
                  </div>
                </div>
                <div class="product-card---bottom-content">
                  <div class="mg-top-default"><div class="divider"></div></div>
                  <div class="div-block-42">
                    <div class="w-layout-grid grid-1-column gap-row-16px">
                      <div class="feature-wrapper dark v1"><div class="check-icon feature-plan" aria-hidden="true">&#xE82F;</div><div class="display-2">Custom Repayment Plans</div></div>
                      <div class="feature-wrapper dark v1"><div class="check-icon feature-plan" aria-hidden="true">&#xE82F;</div><div class="display-2">More Buying Power</div></div>
                      <div class="feature-wrapper dark v1"><div class="check-icon feature-plan" aria-hidden="true">&#xE82F;</div><div class="display-2">Up to 30-Year Terms</div></div>
                      <div class="feature-wrapper dark v1"><div class="check-icon feature-plan" aria-hidden="true">&#xE82F;</div><div class="display-2">Upgrade Without Stress</div></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="section top-none">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="w-layout-grid grid-2-columns values-wrapper-grid">
            <div class="sticky-top static---tablet">
              <div class="inner-container _500px _100-tablet">
                <div class="inner-container _600px---tablet">
                  <div class="mg-top-default"><h2 class="heading-15">4 Easy Steps</h2></div>
                  <div class="mg-top-small">
                    <p>Our step-by-step process is designed to make replacing your windows and doors easy, stress-free, and fully tailored to your needs — from the first estimate to the final inspection.</p>
                  </div>
                </div>
              </div>
            </div>
            <div class="inner-container _592px _100-tablet">
              <div class="w-layout-grid grid-2-columns values-grid">
                <div class="value-wrapper">
                  <div class="image-wrapper"><img src="/webflow-assets/images/684d86f32d344f16ce6ec364_flag_24dp_E3E3E3_FILL0_wght400_GRAD0_opsz24.svg" loading="eager" alt="For-architects-deluxe-windows" class="image" /></div>
                  <div class="mg-top-small"><h3 class="display-5 mid">Start</h3></div>
                  <div class="mg-top-extra-small"><p class="paragraph-5">Looking to replace your windows and doors? Reach out to Deluxe Windows for a complimentary estimate.</p></div>
                </div>
                <div class="value-wrapper">
                  <div class="image-wrapper"><img src="/webflow-assets/images/684d86ff1fff20336f975d74_shopping_bag_24dp_E3E3E3_FILL0_wght400_GRAD0_opsz24.svg" loading="eager" alt="For-contractors-deluxe-windows" class="image" /></div>
                  <div class="mg-top-small"><h3 class="display-5 mid">Manufacture</h3></div>
                  <div class="mg-top-extra-small"><p class="paragraph-6">If you are satisfied with the provided estimate and approve it, we will order windows and doors according to your specifications and needs.</p></div>
                </div>
                <div class="value-wrapper">
                  <div class="image-wrapper"><img src="/webflow-assets/images/684d870c533c4f729eb8094c_settings_24dp_E3E3E3_FILL0_wght400_GRAD0_opsz24.svg" loading="eager" alt="For-property-managers-owners-deluxe-windows" class="image" /></div>
                  <div class="mg-top-small"><h3 class="display-5 mid">Remove and install</h3></div>
                  <div class="mg-top-extra-small"><p class="paragraph-7">Once the products are ready, we will arrange a convenient time for installation and ensure your new windows and doors are expertly fitted.</p></div>
                </div>
                <div class="value-wrapper">
                  <div class="image-wrapper"><img src="/webflow-assets/images/684d8718e99d2a34dfef7e4d_home_24dp_E3E3E3_FILL0_wght400_GRAD0_opsz24.svg" loading="eager" alt="For-property-managers-owners-deluxe-windows" class="image" /></div>
                  <div class="mg-top-small"><h3 class="display-5 mid">Final product</h3></div>
                  <div class="mg-top-extra-small"><p class="paragraph-7">Upon completion, each window and door will be thoroughly inspected to ensure they operate correctly and meet the highest standards of fit and finish.</p></div>
                </div>
                <div class="divider show-in-mbp"></div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="section-card-wrapper">
        <div class="section-card cta-v3">
          <div class="w-layout-blockcontainer container-default w-container">
            <div class="w-layout-grid grid-2-columns cta-v3-grid">
              <div class="z-index-1">
                <div class="inner-container _500px---mbl">
                  <div class="inner-container _480px">
                    <div class="inner-container _450px">
                      <div class="inner-container _300px---mbp">
                        <div class="mg-top-small"><h2 class="heading-36">Your Dream Home Starts Here.</h2></div>
                      </div>
                    </div>
                    <div class="mg-top-small">
                      <div class="text-neutral-light"><p class="paragraph-20">Tell us about your project — we'll take care of the rest.</p></div>
                    </div>
                    <div class="mg-top-default">
                      <div class="buttons-row left">
                        <a href="/contacts" class="primary-button w-inline-block"><div class="text-block">Free Consultation</div></a>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="image-wrapper cta-v3-image">
                <x-img src="/webflow-assets/images/687ca4b70b8583ef4890bad4_iPad.avif" preset="cta" loading="eager" alt="Deluxe-windows" class="image" />
              </div>
            </div>
          </div>
        </div>
      </section>


@endsection

@section('bodyScripts')
    <script src="/webflow-assets/js/jquery-3.5.1.min.js" type="text/javascript"></script>
    <script src="/webflow-assets/js/webflow-doors.js" type="text/javascript"></script>

    <script>
    (function () {
      var mainImg = document.getElementById('dw-main-img');
      var track   = document.getElementById('dw-track');
      var wrapper = document.querySelector('.dw-gallery__track-wrapper');
      var prevBtn = document.getElementById('dw-prev');
      var nextBtn = document.getElementById('dw-next');
      if (!track || !wrapper) return;

      var thumbs = Array.from(track.querySelectorAll('.dw-gallery__thumb'));
      var GAP    = 8;
      var offset = 0;
      var active = 0;

      function isMobile() { return window.innerWidth <= 767; }
      function getVisible() { return isMobile() ? 1 : 6; }
      function stepPx() {
        var w = wrapper.offsetWidth;
        if (isMobile()) return w + GAP;
        return (w - 5 * GAP) / 6 + GAP;
      }
      function applyOffset() {
        track.style.transform = 'translateX(-' + (offset * stepPx()) + 'px)';
      }
      function updateArrows() {
        var vis = getVisible();
        if (prevBtn) prevBtn.disabled = offset <= 0;
        if (nextBtn) nextBtn.disabled = offset >= thumbs.length - vis;
      }
      function setActive(idx) {
        var vis = getVisible();
        active = idx;
        thumbs.forEach(function (t, i) { t.classList.toggle('is-active', i === idx); });
        if (mainImg && !isMobile()) {
          mainImg.style.opacity = '0';
          setTimeout(function () {
            mainImg.src = thumbs[idx].dataset.src;
            mainImg.style.opacity = '1';
          }, 180);
        }
        if (idx < offset) { offset = idx; }
        else if (idx >= offset + vis) { offset = idx - vis + 1; }
        applyOffset();
        updateArrows();
      }
      thumbs.forEach(function (t, i) {
        t.addEventListener('click', function () { setActive(i); });
      });
      if (prevBtn) prevBtn.addEventListener('click', function () {
        if (offset > 0) {
          offset--;
          if (isMobile()) setActive(offset);
          else { applyOffset(); updateArrows(); }
        }
      });
      if (nextBtn) nextBtn.addEventListener('click', function () {
        if (offset < thumbs.length - getVisible()) {
          offset++;
          if (isMobile()) setActive(offset);
          else { applyOffset(); updateArrows(); }
        }
      });
      var tx0 = 0;
      track.addEventListener('touchstart', function (e) { tx0 = e.touches[0].clientX; }, { passive: true });
      track.addEventListener('touchend', function (e) {
        var dx = tx0 - e.changedTouches[0].clientX;
        if (Math.abs(dx) > 40) {
          if (dx > 0 && active < thumbs.length - 1) setActive(active + 1);
          else if (dx < 0 && active > 0) setActive(active - 1);
        }
      }, { passive: true });
      window.addEventListener('resize', function () {
        offset = Math.min(offset, Math.max(0, thumbs.length - getVisible()));
        applyOffset();
        updateArrows();
      });
      updateArrows();
    })();
    </script>

    <script>
      (function () {
        const TRACK_PARAMS = ["utm_source", "utm_medium", "utm_campaign", "utm_content", "utm_term", "gclid", "fbclid", "msclkid"];
        const urlParams = new URLSearchParams(window.location.search);
        const hasSavedUtm = TRACK_PARAMS.some(p => localStorage.getItem("lead_param_" + p));
        if (!hasSavedUtm) {
          TRACK_PARAMS.forEach(param => {
            const val = urlParams.get(param);
            if (val) localStorage.setItem("lead_param_" + param, val);
          });
        }
        if (!localStorage.getItem("lead_param_landing_page")) {
          localStorage.setItem("lead_param_landing_page", window.location.pathname);
        }
      })();
    </script>
@endsection

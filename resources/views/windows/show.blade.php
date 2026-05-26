<!DOCTYPE html>
<html
  data-wf-domain="www.deluxewindows.com"
  data-wf-site="6841ddf8ace3d9d9facb14fd"
  lang="en"
>
  <head>
    <meta charset="utf-8" />
    <link href="https://cdn.prod.website-files.com" rel="preconnect" crossorigin="anonymous" />
    <title>{{ $seoTitle }} | Deluxe Windows Concord</title>
    <meta content="{{ $seoDescription }}" name="description" />
    <meta content="{{ $ogTitle }}" property="og:title" />
    <meta content="{{ $ogDescription }}" property="og:description" />
    @if($ogImage)
    <meta content="{{ $ogImage }}" property="og:image" />
    @endif
    <meta content="{{ $ogTitle }}" name="twitter:title" />
    <meta content="{{ $ogDescription }}" name="twitter:description" />
    @if($ogImage)
    <meta content="{{ $ogImage }}" name="twitter:image" />
    @endif
    <meta property="og:type" content="website" />
    <meta content="summary_large_image" name="twitter:card" />
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <link href="/webflow-assets/css/webflow.min.css" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="/webflow-assets/css/fonts.css" media="all" />
    <script type="text/javascript">
      document.documentElement.className = document.documentElement.className
        .replace(/\bwf-loading\b/g, 'wf-active')
        .replace(/\bwf-exo-[^\s]+/g, '');
    </script>
    <script type="text/javascript">
      !(function (o, c) {
        var n = c.documentElement,
          t = " w-mod-";
        n.className += t + "js";
        ("ontouchstart" in o || (o.DocumentTouch && c instanceof DocumentTouch)) &&
          (n.className += t + "touch");
      })(window, document);
    </script>
    <link
      href="/webflow-assets/images/favicon.png"
      rel="shortcut icon"
      type="image/x-icon"
    />
    <link href="/webflow-assets/images/webclip-bg.png" rel="apple-touch-icon" />

    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Product",
      "name": {!! json_encode($title) !!},
      "description": {!! json_encode($seoDescription) !!},
      "url": "/windows/{!! $windowFieldData['slug'] ?? '' !!}",
      "image": {!! json_encode($ogImage) !!},
      "brand": { "@type": "Brand", "name": "Deluxe Windows" },
      "offers": {
        "@type": "AggregateOffer",
        "priceCurrency": "USD",
        "availability": "https://schema.org/InStock"
      }
    }
    </script>

    <style>
      .w-webflow-badge { display: none !important; }
      .section.top-none { margin-top: 0 !important; }

      /* ── Custom product gallery ── */
      .dw-gallery {
        width: 100%;
        --dw-gap: 8px;
        --dw-arrow: 36px;
        --dw-ratio: 4 / 3;
      }

      /* Main image — full width, same ratio as thumbs */
      .dw-gallery__main {
        width: 100%;
        aspect-ratio: var(--dw-ratio);
        overflow: hidden;
        border-radius: 12px;
        margin-bottom: 10px;
        background: #f1f5f9;
      }
      .dw-gallery__main img {
        width: 100%; height: 100%; object-fit: cover;
        transition: opacity .2s ease;
      }

      /* Strip row: [arrow] [track] [arrow]
         Track is exactly as wide as the main image (no extra indent) */
      .dw-gallery__row {
        display: grid;
        grid-template-columns: var(--dw-arrow) 1fr var(--dw-arrow);
        align-items: center;
        gap: var(--dw-gap);
      }

      /* Arrows */
      .dw-gallery__arrow {
        width: var(--dw-arrow); height: var(--dw-arrow);
        border-radius: 50%;
        border: 1.5px solid #cbd5e1;
        background: #fff;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; color: #334155;
        transition: background .2s, border-color .2s, color .2s;
        padding: 0; line-height: 0; flex-shrink: 0;
      }
      .dw-gallery__arrow:hover  { background: #f1f5f9; border-color: #64748b; color: #0f172a; }
      .dw-gallery__arrow:disabled { opacity: .3; cursor: default; pointer-events: none; }

      /* Track */
      .dw-gallery__track-wrapper { overflow: hidden; }
      .dw-gallery__track {
        display: flex;
        gap: var(--dw-gap);
        transition: transform .3s cubic-bezier(.4,0,.2,1);
        will-change: transform;
      }

      /* Thumbnails — 6 per row, same ratio as main */
      .dw-gallery__thumb {
        flex: 0 0 calc((100% - 5 * var(--dw-gap)) / 6);
        aspect-ratio: var(--dw-ratio);
        overflow: hidden;
        border-radius: 6px;
        border: 2px solid transparent;
        cursor: pointer;
        padding: 0;
        background: #f1f5f9;
        transition: border-color .2s;
      }
      .dw-gallery__thumb.is-active        { border-color: #2563eb; }
      .dw-gallery__thumb:hover:not(.is-active) { border-color: #94a3b8; }
      .dw-gallery__thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }

      /* ── Mobile: hide main image, show full-width carousel ── */
      @media (max-width: 767px) {
        .dw-gallery__main { display: none; }

        /* Each thumb becomes a full-width slide */
        .dw-gallery__thumb {
          flex: 0 0 100% !important;
          aspect-ratio: 4 / 3 !important;
          border-radius: 10px !important;
          border-color: transparent !important;
        }
        .dw-gallery__thumb.is-active { border-color: transparent !important; }

        /* Bigger arrows on mobile */
        .dw-gallery__arrow {
          width: 40px; height: 40px;
          background: rgba(255,255,255,.9);
          border-color: #94a3b8;
          box-shadow: 0 1px 4px rgba(0,0,0,.12);
        }
      }
    </style>

    <!-- Google tag -->
    <script>
      (function () {
        let gtagLoaded = false;
        function loadGtag() {
          if (gtagLoaded) return;
          gtagLoaded = true;
          const script = document.createElement("script");
          script.src = "https://www.googletagmanager.com/gtag/js?id=G-JHYBB0THJM";
          script.async = true;
          document.head.appendChild(script);
          window.dataLayer = window.dataLayer || [];
          function gtag() { dataLayer.push(arguments); }
          window.gtag = gtag;
          gtag("js", new Date());
          gtag("config", "G-JHYBB0THJM");
          gtag("config", "AW-1030787786");
        }
        window.addEventListener("scroll", loadGtag, { once: true });
        window.addEventListener("click", loadGtag, { once: true });
        setTimeout(loadGtag, 3000);
      })();
    </script>

    <script>
      window.$zoho = window.$zoho || {};
      $zoho.salesiq = $zoho.salesiq || { ready: function () {} };
    </script>

    <link href="https://core.service.elfsight.com/" rel="preconnect" crossorigin="" />
  </head>

  <body class="body-18 height-auto">
    <div class="page-wrapper">

      @include('partials.navbar')

      @include('partials.hero', ['windowHeroImage' => $heroImage])

      @include('partials.trust-badges')

      {{-- Breadcrumbs --}}
      <section class="section_breadcrumbs section-121">
        <div class="w-layout-blockcontainer container-default breadcrumbs-container w-container">
          <div class="breadcrumbs-wrapper">
            <a href="/" class="breadcrumb-link">Home</a>
            <div class="breadcrumb-div">/</div>
            <a href="/windows" class="breadcrumb-link hidden-link">Windows</a>
            <div class="breadcrumb-div hidden-txt">/</div>
            <div class="breadcrumb-text">{{ $title }}</div>
          </div>
        </div>
      </section>

      {{-- Main product section --}}
      <section class="section pd-120px top-none">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="mg-top-extra-large">
            <div class="w-layout-grid grid-2-columns listing-grid">

              {{-- Left: title, summary, about --}}
              <div class="inner-container _690px _100-tablet">
                <div>
                  <h1 class="display-8 mid">{{ $title }}</h1>
                  @if($summary)
                  <div class="mg-top-small">
                    <p class="paragraph-14">{{ $summary }}</p>
                  </div>
                  @endif
                  @if($discountHtml)
                  <div class="mg-top-small">
                    {!! $discountHtml !!}
                  </div>
                  @endif
                  <div class="divider mg-extra-large"></div>
                  @if($aboutHtml)
                  <div>
                    <div class="rich-text-v2 mg-bottom--16px w-richtext">
                      {!! $aboutHtml !!}
                    </div>
                  </div>
                  @endif
                </div>
              </div>

              {{-- Right: custom gallery (main image + scrollable thumbnail strip) --}}
              @php
                $allGalleryImages = collect();
                if ($heroImage) $allGalleryImages->push($heroImage);
                foreach ($galleryImages as $gi) $allGalleryImages->push($gi);
              @endphp
              <div class="dw-gallery" id="dw-gallery">

                {{-- Main large image --}}
                <div class="dw-gallery__main">
                  <img
                    id="dw-main-img"
                    src="{{ $allGalleryImages->first() ?? '' }}"
                    alt="{{ $title }}"
                    loading="eager"
                  />
                </div>

                {{-- Thumbnail strip with arrows outside track edges --}}
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
                        data-src="{{ $img }}"
                        data-idx="{{ $idx }}"
                        aria-label="Image {{ $idx + 1 }}"
                      ><img src="{{ $img }}" alt="{{ $title }} {{ $idx + 1 }}" loading="lazy" /></button>
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
        </div>
      </section>

      {{-- Our Guarantee (window-specific) --}}
      <div class="f-section-large-3">
        <div class="f-container-regular-3">
          <div class="title-left---content-right dva">
            <h2 class="heading-23">Our Guarantee</h2>
          </div>
          <div class="w-layout-grid f-grid-three-column-2 newww">
            <div class="f-feature-card-filled">
              <div class="f-margin-bottom-129">
                <h5 class="f-h5-heading">{{ $title }}</h5>
                @if($warrantyHtml)
                <div class="rich-text-block-10 w-richtext">
                  {!! $warrantyHtml !!}
                </div>
                @else
                <div class="rich-text-block-10 w-richtext">
                  <p><strong>Full lifetime</strong> transferable warranty on parts and labor</p>
                </div>
                @endif
              </div>
            </div>
            <div class="f-feature-card-filled">
              <div class="f-margin-bottom-129">
                <h5 class="f-h5-heading">Manufacturer&#x27;s warranty on glass and frame</h5>
              </div>
              <p class="f-paragraph-large-2"><br /><strong>Lifetime</strong></p>
            </div>
            <div class="f-feature-card-filled">
              <div class="f-margin-bottom-129">
                <h5 class="f-h5-heading">All Other Parts</h5>
              </div>
              <p class="f-paragraph-large-2"><strong><br />10&nbsp;Years</strong> Warranty</p>
            </div>
          </div>
        </div>
      </div>

      {{-- Top brands for this window type --}}
      @if($brandTypeItems->count() > 0)
      <section class="section top-none">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="title-left---content-right">
            <h2 class="heading-23">{{ $titleForBrands }}</h2>
          </div>
          <div class="mg-top-large">
            <div class="collection-list-wrapper-5 w-dyn-list">
              <div role="list" class="collection-list-2 w-dyn-items">
                @foreach($brandTypeItems as $brand)
                <div role="listitem" class="w-dyn-item">
                  <a href="/window-type/{{ $brand['slug'] }}" class="property-wrapper-v1 w-inline-block">
                    <div class="property-card-top-content-v1">
                      <div class="image-wrapper border-radius-image-default property-card-top-content-v1---image">
                        <img
                          src="{{ $brand['image'] }}"
                          loading="eager"
                          alt="{{ $brand['name'] }}"
                          class="image cover-image"
                        />
                      </div>
                    </div>
                  </a>
                </div>
                @endforeach
              </div>
            </div>
          </div>
          <div class="mg-top-large">
            <div class="buttons-row">
              <a href="/windows" class="primary-button w-inline-block">
                <div class="text-block-22">All Window Types</div>
              </a>
            </div>
          </div>
        </div>
      </section>
      @endif

      {{-- Financing section --}}
      <section class="section hero-v4">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="inner-container _600px center">
            <div class="text-center">
              <div class="inner-container _500px---mbl center">
                <h1 class="heading-20">Upgrade your entire home today with flexible payment options that</h1>
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
                    <div class="mg-top-small mg-top-16px---mbl">
                      <h2 class="display-5 mid">No FICO</h2>
                    </div>
                    <div class="mg-top-extra-small">
                      <p>Your credit rating does not impact your ability to qualify.</p>
                    </div>
                  </div>
                </div>
                <div class="product-card---bottom-content">
                  <div class="mg-top-default"><div class="divider"></div></div>
                  <div class="mg-top-small">
                    <div class="w-layout-grid grid-1-column gap-row-16px">
                      <div class="feature-wrapper dark v1"><div class="check-icon feature-plan">вњ“</div><div class="display-2">No Credit Score Required</div></div>
                      <div class="feature-wrapper dark v1"><div class="check-icon feature-plan">вњ“</div><div class="display-2">Fast &amp; Simple Approval</div></div>
                      <div class="feature-wrapper dark v1"><div class="check-icon feature-plan">вњ“</div><div class="display-2">Improve Home Value Now</div></div>
                      <div class="feature-wrapper dark v1"><div class="check-icon feature-plan">вњ“</div><div class="display-2">Second Chance Financing</div></div>
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
                    <div class="mg-top-small mg-top-16px---mbl">
                      <h2 class="display-5 mid">Lower Fixed Rates</h2>
                    </div>
                    <div class="mg-top-extra-small">
                      <p>Payment remains the same for the life of your financing.</p>
                    </div>
                  </div>
                </div>
                <div class="product-card---bottom-content">
                  <div class="mg-top-default"><div class="divider"></div></div>
                  <div class="mg-top-small">
                    <div class="w-layout-grid grid-1-column gap-row-16px">
                      <div class="feature-wrapper dark v1"><div class="check-icon feature-plan">вњ“</div><div class="display-2">Budget-Friendly Payments</div></div>
                      <div class="feature-wrapper dark v1"><div class="check-icon feature-plan">вњ“</div><div class="display-2">Long-Term Savings</div></div>
                      <div class="feature-wrapper dark v1"><div class="check-icon feature-plan">вњ“</div><div class="display-2">Secure &amp; Stable</div></div>
                      <div class="feature-wrapper dark v1"><div class="check-icon feature-plan">вњ“</div><div class="display-2">Immediate Upgrades</div></div>
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
                    <div class="mg-top-small mg-top-16px---mbl">
                      <h2 class="display-5 mid">Longer Terms</h2>
                    </div>
                    <div class="mg-top-extra-small">
                      <p>Flexible repayment terms - up to 30 years for some projects.</p>
                    </div>
                  </div>
                </div>
                <div class="product-card---bottom-content">
                  <div class="mg-top-default"><div class="divider"></div></div>
                  <div class="mg-top-small">
                    <div class="w-layout-grid grid-1-column gap-row-16px">
                      <div class="feature-wrapper dark v1"><div class="check-icon feature-plan">вњ“</div><div class="display-2">Custom Repayment Plans</div></div>
                      <div class="feature-wrapper dark v1"><div class="check-icon feature-plan">вњ“</div><div class="display-2">More Buying Power</div></div>
                      <div class="feature-wrapper dark v1"><div class="check-icon feature-plan">вњ“</div><div class="display-2">Up to 30-Year Terms</div></div>
                      <div class="feature-wrapper dark v1"><div class="check-icon feature-plan">вњ“</div><div class="display-2">Upgrade Without Stress</div></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {{-- 4 Easy Steps --}}
      <section class="section top-none"><div class="w-layout-blockcontainer container-default w-container"><div class="w-layout-grid grid-2-columns values-wrapper-grid"><div class="sticky-top static---tablet"><div class="inner-container _500px _100-tablet"><div class="inner-container _600px---tablet"><div class="mg-top-default"><h2 class="heading-8">4 Easy Steps</h2></div><div class="mg-top-small"><p class="paragraph-34">Our step-by-step process is designed to make replacing your windows and doors easy, stress-free, and fully tailored to your needs - from the first estimate to the final inspection.</p></div><div class="mg-top-default"><div class="buttons-row left"></div></div></div></div></div><div class="inner-container _592px _100-tablet"><div class="w-layout-grid grid-2-columns values-grid"><div class="value-wrapper"><div class="image-wrapper"><img src="/webflow-assets/images/684d86f32d344f16ce6ec364_flag_24dp_E3E3E3_FILL0_wght400_GRAD0_opsz24.svg" loading="eager" alt="For-architects-deluxe-windows" class="image"/></div><div class="mg-top-small"><h3 class="display-5 mid">Start</h3></div><div class="mg-top-extra-small"><p class="paragraph-5">Looking to replace your windows and doors? Reach out to Deluxe Windows for a complimentary estimate.</p></div></div><div class="value-wrapper"><div class="image-wrapper"><img src="/webflow-assets/images/684d86ff1fff20336f975d74_shopping_bag_24dp_E3E3E3_FILL0_wght400_GRAD0_opsz24.svg" loading="eager" alt="For-contractors-deluxe-windows" class="image"/></div><div class="mg-top-small"><h3 class="display-5 mid">Manufacture</h3></div><div class="mg-top-extra-small"><p class="paragraph-6">If you are satisfied with the provided estimate and approve it, we will order windows and doors according to your specifications and needs.</p></div></div><div class="value-wrapper"><div class="image-wrapper"><img src="/webflow-assets/images/684d870c533c4f729eb8094c_settings_24dp_E3E3E3_FILL0_wght400_GRAD0_opsz24.svg" loading="eager" alt="For-property-managers-owners-deluxe-windows" class="image"/></div><div class="mg-top-small"><h3 class="display-5 mid">Remove and install</h3></div><div class="mg-top-extra-small"><p class="paragraph-7">Once the products are ready, we will arrange a convenient time for installation and ensure your new windows and doors are expertly fitted.</p></div></div><div class="value-wrapper"><div class="image-wrapper"><img src="/webflow-assets/images/684d8718e99d2a34dfef7e4d_home_24dp_E3E3E3_FILL0_wght400_GRAD0_opsz24.svg" loading="eager" alt="For-property-managers-owners-deluxe-windows" class="image"/></div><div class="mg-top-small"><h3 class="display-5 mid">Final product</h3></div><div class="mg-top-extra-small"><p class="paragraph-7">Upon completion, each window and door will be thoroughly inspected to ensure they operate correctly and meet the highest standards of fit and finish.</p></div></div><div class="divider show-in-mbp"></div></div></div></div></div></section>

      {{-- CTA section --}}
      <section class="section-card-wrapper"><div class="section-card cta-v3"><div class="w-layout-blockcontainer container-default w-container"><div class="w-layout-grid grid-2-columns cta-v3-grid"><div class="z-index-1"><div class="inner-container _500px---mbl"><div class="inner-container _480px"><div class="inner-container _450px"><div class="inner-container _300px---mbp"><div class="mg-top-small"><h2 class="heading-25">Your dream home starts here.</h2></div></div></div><div class="mg-top-small"><div class="text-neutral-light"><p class="paragraph-20">Tell us about your project - we'll take care of the rest.</p></div></div><div class="mg-top-default"><div class="buttons-row left"><a href="#" class="primary-button w-inline-block"><div class="text-block">Free Consultation</div></a></div></div></div></div></div><div class="image-wrapper cta-v3-image"><img src="/webflow-assets/images/687ca4b70b8583ef4890bad4_iPad.avif" loading="eager" alt="Deluxe-windows" class="image"/></div></div></div></div></section>

      {{-- FAQ section --}}
      <section class="section top-none"><div class="w-layout-blockcontainer container-default w-container"><div class="w-layout-grid grid-2-columns faqs-grid-v3"><div class="sticky-top static---mbl"><div class="inner-container _450px---mbl"><div class="inner-container _275px---tablet _100-mbl"><div class="inner-container _340px _100-mbl"><div class="mg-top-small"><h2 class="heading-44">Do You Have Any Question?</h2></div><div class="div-block-49"><p class="paragraph-2">Call us at <a href="tel:855-355-0515">(650) 461-4446</a> to <br/>ask your questions. </p></div></div></div></div></div><div class="inner-container _763px width-100"><div class="card accordion-card v2"><div class="w-layout-grid grid-1-column accordion-v6"><div class="accordion-item-wrapper v2 first"><div class="accordion-top"><div class="text-titles"><h3 class="faqs-title">Which material is best for your windows?<br/></h3></div><div class="accordion-icon-wrapper"><div class="accordion-icon-line vertical"></div><div class="accordion-icon-line"></div></div></div><div class="accordion-bottom v1"><p class="accordion-paragraph">The best window material depends on your home&#x27;s style, climate, energy efficiency needs, and budget. We offer a variety of options like vinyl, wood, aluminum, and fiberglass - each with its own benefits.<br/><br/>To find the perfect fit for your home, we recommend speaking with one of our experts. Contact us today for a personalized consultation</p></div></div><div data-w-id="5e6fa5f4-992b-f428-8721-43b1fd267cb8" class="accordion-wrapper"><div class="accordion-item-wrapper v2"><div class="accordion-top"><div class="text-titles"><h3 class="faqs-title">Is consultation for free?</h3></div><div class="accordion-icon-wrapper"><div class="accordion-icon-line vertical"></div><div class="accordion-icon-line"></div></div></div><div class="accordion-bottom v1"><p class="accordion-paragraph">To get a free consultation, please fill out the <a href="#">form</a>.</p></div></div></div><div data-w-id="5e6fa5f4-992b-f428-8721-43b1fd267cc6" class="accordion-wrapper"><div class="accordion-item-wrapper v2"><div class="accordion-top"><div class="text-titles"><h3 class="faqs-title">When do I need new windows?</h3></div><div class="accordion-icon-wrapper"><div class="accordion-icon-line vertical"></div><div class="accordion-icon-line"></div></div></div><div class="accordion-bottom v1"><p class="accordion-paragraph">If you aren&#x27;t sure whether your windows need replacing, Deluxe Windows, Inc. can come to your home for a free consultation.</p></div></div></div><div data-w-id="5e6fa5f4-992b-f428-8721-43b1fd267cd6" class="accordion-wrapper"><div class="accordion-item-wrapper v2 last"><div class="accordion-top"><div class="text-titles"><h3 class="faqs-title">How to choose windows brands and styles?</h3></div><div class="accordion-icon-wrapper"><div class="accordion-icon-line vertical"></div><div class="accordion-icon-line"></div></div></div><div class="accordion-bottom v1"><p class="accordion-paragraph">The answer to this question can only be answered once we come to your home for a free consultation. Every home is different, and when our professional window replacement specialist comes out to assess your house, we can factor in all the different aspects to suggest which product, style and price range will work best for you.</p></div></div></div></div></div></div></div></div></section>

      <section class="new-section"><div class="w-layout-blockcontainer container-default w-container"><div class="text-block-44">* Price applies to minimum window installation size of 24&quot;x24&quot;</div></div></section>

      @include('partials.footer')

    </div>{{-- end .page-wrapper --}}

    <div id="menuDimmer" style="opacity: 0; pointer-events: none"></div>

    <script src="/webflow-assets/js/jquery-3.5.1.min.js" type="text/javascript"></script>
    <script src="/webflow-assets/js/webflow-windows.js" type="text/javascript"></script>

    {{-- Custom gallery: click thumbnail → update main image; mobile → full carousel --}}
    <script>
    (function () {
      var mainImg = document.getElementById('dw-main-img');
      var track   = document.getElementById('dw-track');
      var prevBtn = document.getElementById('dw-prev');
      var nextBtn = document.getElementById('dw-next');
      if (!track) return;

      var thumbs  = Array.from(track.querySelectorAll('.dw-gallery__thumb'));
      var offset  = 0;
      var active  = 0;

      function isMobile() { return window.innerWidth <= 767; }
      function getVisible() { return isMobile() ? 1 : 6; }

      /* Step size = thumb rendered width + gap (8px) */
      function stepPx() {
        return thumbs[0] ? thumbs[0].getBoundingClientRect().width + 8 : 0;
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

        /* Update main image (desktop only) */
        if (mainImg && !isMobile()) {
          mainImg.style.opacity = '0';
          setTimeout(function () {
            mainImg.src = thumbs[idx].dataset.src;
            mainImg.style.opacity = '1';
          }, 180);
        }

        /* Auto-scroll track to keep active thumb visible */
        if (idx < offset) { offset = idx; }
        else if (idx >= offset + vis) { offset = idx - vis + 1; }
        applyOffset();
        updateArrows();
      }

      thumbs.forEach(function (t, i) {
        t.addEventListener('click', function () { setActive(i); });
      });

      if (prevBtn) prevBtn.addEventListener('click', function () {
        if (offset > 0) { offset--; applyOffset(); updateArrows(); }
        if (isMobile()) { setActive(offset); }
      });
      if (nextBtn) nextBtn.addEventListener('click', function () {
        if (offset < thumbs.length - getVisible()) { offset++; applyOffset(); updateArrows(); }
        if (isMobile()) { setActive(offset); }
      });

      /* Touch swipe support (mobile) */
      var touchStartX = 0;
      track.addEventListener('touchstart', function (e) {
        touchStartX = e.touches[0].clientX;
      }, { passive: true });
      track.addEventListener('touchend', function (e) {
        var dx = touchStartX - e.changedTouches[0].clientX;
        if (Math.abs(dx) > 40) {
          if (dx > 0 && active < thumbs.length - 1) { setActive(active + 1); }
          else if (dx < 0 && active > 0)            { setActive(active - 1); }
        }
      }, { passive: true });

      /* Recalculate on resize */
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
        /* UTM capture */
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
  </body>
</html>


<!DOCTYPE html>
<html
  data-wf-domain="www.deluxewindows.com"
  data-wf-site="6841ddf8ace3d9d9facb14fd"
  lang="en"
>
  <head>
    <meta charset="utf-8" />
    <title>{{ $seoTitle }} | Deluxe Windows</title>
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
    <link href="/webflow-assets/images/favicon.png" rel="shortcut icon" type="image/x-icon" />
    <link href="/webflow-assets/images/webclip-bg.png" rel="apple-touch-icon" />

    <style>
      .w-webflow-badge { display: none !important; }
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

      @include('partials.hero', ['windowHeroImage' => null])

      @include('partials.trust-badges')

      {{-- Breadcrumbs --}}
      <section class="section_breadcrumbs section-121">
        <div class="w-layout-blockcontainer container-default breadcrumbs-container w-container">
          <div class="breadcrumbs-wrapper">
            <a href="/" class="breadcrumb-link">Home</a>
            <div class="breadcrumb-div">/</div>
            <a href="/brands" class="breadcrumb-link hidden-link">Brands</a>
            <div class="breadcrumb-div hidden-txt">/</div>
            <div class="breadcrumb-text">{{ $name }}</div>
          </div>
        </div>
      </section>

      {{-- Brand main section: sidebar + content --}}
      <section class="section_sidebar brands">
        <div data-delay="0" data-hover="false" class="dropdown-tab tabs-mob sidebar-dropdown w-dropdown">
          <div data-dd="toggle" class="toggle-tab tabs-mob sidebar is-first brands w-dropdown-toggle" aria-haspopup="menu" aria-expanded="false" role="button" tabindex="0">
            <div class="toggle-text-tab-2 sidebar-txt">All collections</div>
            <div class="tab-icon-wrapper sidebar-icon-wrapper">
              <svg xmlns="http://www.w3.org/2000/svg" width="100%" viewBox="0 0 9 6" fill="none" class="sidebar-icon">
                <path d="M4.5 5.55005L0 1.05005L1.05 4.86076e-05L4.5 3.45005L7.95 4.86076e-05L9 1.05005L4.5 5.55005Z" fill="currentColor"></path>
              </svg>
            </div>
          </div>
          <nav data-dd="list" class="dropdown-list-4 sidebar-list w-dropdown-list">
            <div class="sidebar_content-wrapper-2 bottom brands">
              @if($logo)
              <img loading="lazy" src="{{ $logo }}" alt="{{ $name }}" class="svg50 sidebar-svg top-svg" width="300" height="150" />
              @endif
              <a href="/brands" class="all-brands-block w-inline-block">
                <div class="icon-font-rounded arrow">&#xE822;</div>
                <div class="text-size-14">All brands</div>
              </a>
              @if($windowTypes->count() > 0)
              <div class="scroll-block">
                <div data-delay="0" data-hover="false" class="dropdown-2 w-dropdown">
                  <div class="dropdown-toogle-2 dd-toggle sidebar-toggle w-dropdown-toggle" aria-haspopup="menu" aria-expanded="false" role="button" tabindex="0">
                    <div class="text-size-16 text-color-grey">{{ $windowsTitle }}</div>
                    <div class="icon-font-rounded-2 dropdown-arrow sidebar-icon hidden">&#x0491;</div>
                  </div>
                  <nav class="dropdown-list-2 dd-sidebar no-borders w-dropdown-list">
                    <div class="w-dyn-list">
                      <div role="list" class="dropdown-list-2 no-padding d-sidebar w-dyn-items">
                        @foreach($windowTypes as $wt)
                        <div role="listitem" class="w-dyn-item">
                          <a href="/window-type/{{ $wt['slug'] }}" class="sidebar-item-2 w-inline-block">
                            @if($wt['image'])
                            <img loading="lazy" src="{{ $wt['image'] }}" alt="{{ $wt['name'] }}" class="sidebar-img" />
                            @endif
                            <div class="sidebar-txt text-size-16">{{ $wt['name'] }}</div>
                          </a>
                        </div>
                        @endforeach
                      </div>
                    </div>
                  </nav>
                </div>
              </div>
              @endif
            </div>

            {{-- Sidebar form (mobile) --}}
            <div class="card-2 sidebar-v1---card new-design brands">
              <div class="form-sidebar">
                <div class="form-block-3 w-form">
                  <form name="wf-form-Brand-Sidebar" method="get" class="form-wrapper" aria-label="Brand Form">
                    <div class="grid-1-column-2 gap-row-12">
                      <div class="input-wrapper-5">
                        <div class="input-line-icon-wrapper-4"><div class="filled-icons-font">&#xF416;</div></div>
                        <input class="input-2 icon-left w-input" maxlength="256" name="Name" placeholder="Full name" type="text" required />
                      </div>
                      <div class="input-wrapper-5">
                        <div class="input-line-icon-wrapper-4"><div class="filled-icons-font">&#xF40F;</div></div>
                        <input class="input-2 icon-left w-input" maxlength="256" name="Email" placeholder="Email address" type="email" required />
                      </div>
                      <div class="input-wrapper-5">
                        <div class="input-line-icon-wrapper-4"><div class="filled-icons-font">&#xF0B3;</div></div>
                        <input class="input-2 icon-left w-input" maxlength="256" name="Phone" placeholder="Phone number" type="tel" required />
                      </div>
                      <div class="input-wrapper-5">
                        <input class="input-2 icon-left w-input" maxlength="256" name="Subject" placeholder="City" type="text" required />
                        <div class="input-line-icon-wrapper">
                          <img loading="eager" src="/webflow-assets/images/6841ddf8ace3d9d9facb194d_star-icon-property-x-webflow-template.svg" alt="Star Icon" style="width:18px;height:18px;object-fit:contain;" />
                        </div>
                      </div>
                      <div class="primary-button-6 space-between-v1">
                        <input type="submit" data-wait="Please wait..." class="inside-input-button-4 text-light w-button" value="Get Your Free Estimate" />
                      </div>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </nav>
        </div>

        {{-- Desktop hero form card --}}
        <div class="card-2 sidebar-v1---card new-design hero-section">
          <div class="inner-container _400px---mbl">
            <div class="text-titles-3">
              <div class="display-41 mid">Get Deluxe Windows for Less. 40% OFF* Windows</div>
            </div>
            <div class="mg-top-small-4">
              <p class="text-titles-3"><em>Request a FREE No-Obligation Quote &amp; Expert Advice!</em></p>
            </div>
          </div>
          <div class="mg-top-default-4">
            <div class="sidebar-form-block-v1 sidebar w-form">
              <form name="wf-form-Brand-Hero" method="get" class="form-wrapper" aria-label="Brand Hero Form">
                <div class="grid-1-column-2 gap-row-12">
                  <div class="input-wrapper-5">
                    <div class="input-line-icon-wrapper-4"><div class="filled-icons-font">&#xF416;</div></div>
                    <input class="input-2 icon-left w-input" maxlength="256" name="Name" placeholder="Full name" type="text" required />
                  </div>
                  <div class="input-wrapper-5">
                    <div class="input-line-icon-wrapper-4"><div class="filled-icons-font">&#xF40F;</div></div>
                    <input class="input-2 icon-left w-input" maxlength="256" name="Email" placeholder="Email address" type="email" required />
                  </div>
                  <div class="input-wrapper-5">
                    <div class="input-line-icon-wrapper-4"><div class="filled-icons-font">&#xF0B3;</div></div>
                    <input class="input-2 icon-left w-input" maxlength="256" name="Phone" placeholder="Phone number" type="tel" required />
                  </div>
                  <div class="input-wrapper-5">
                    <input class="input-2 icon-left w-input" maxlength="256" name="Subject" placeholder="City" type="text" required />
                    <div class="input-line-icon-wrapper">
                      <img loading="eager" src="/webflow-assets/images/6841ddf8ace3d9d9facb194d_star-icon-property-x-webflow-template.svg" alt="Star Icon" style="width:18px;height:18px;object-fit:contain;" />
                    </div>
                  </div>
                  <div class="primary-button-6 space-between-v1">
                    <input type="submit" data-wait="Please wait..." class="inside-input-button-4 text-light w-button" value="Get Your Free Estimate" />
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>

        {{-- Brand name + description --}}
        <div class="inner-container _690px _100-tablet">
          <div class="div-block-52 brandmob">
            <h1 class="display-8 mid types">{{ $name }}</h1>
          </div>
          @if($description)
          <div class="rich-text-v2 mg-bottom--16px w-richtext">
            {!! nl2br(e($description)) !!}
          </div>
          @endif
        </div>

      </section>

      {{-- Explore Brand's Window Types --}}
      @if($windowTypes->count() > 0)
      <div class="w-layout-blockcontainer container-default w-container">
        <div class="title-left---content-right">
          <h2 class="heading-20">{{ $windowsTitle }}</h2>
        </div>
        <div class="mg-top-large">
          <div class="collection-list-wrapper-21 w-dyn-list">
            <div role="list" class="grid-2-columns properties-grid---v1 collection-list w-dyn-items">
              @foreach($windowTypes as $wt)
              <div id="w-node-_4681f2dd-d688-84d2-cc5c-d18cdd46c664-facb1583" role="listitem" class="w-dyn-item">
                <a href="/window-type/{{ $wt['slug'] }}" class="property-wrapper-v1 w-inline-block">
                  <div class="property-card-top-content-v1">
                    <div class="image-wrapper border-radius-image-default property-card-top-content-v1---image">
                      @if($wt['image'])
                      <img src="{{ $wt['image'] }}" loading="eager" alt="{{ $wt['name'] }}" class="image cover-image" />
                      @endif
                    </div>
                  </div>
                  <div class="property-card-bottom-content-v1">
                    <div><h3 class="display-5">{{ $wt['name'] }}</h3></div>
                  </div>
                </a>
              </div>
              @endforeach
            </div>
          </div>
        </div>
      </div>
      @endif

      {{-- Our Guarantee --}}
      <div class="f-section-large-3">
        <div class="f-container-regular-3">
          <div class="title-left---content-right dva">
            <h2 class="heading-23">Our Guarantee</h2>
          </div>
          <div class="w-layout-grid f-grid-three-column-2 newww">
            <div class="f-feature-card-filled">
              <div class="f-margin-bottom-129">
                <h5 class="f-h5-heading">{{ $name }}</h5>
                <div class="rich-text-block-10 w-richtext">
                  <p><strong>Full lifetime</strong> transferable warranty on parts and labor</p>
                </div>
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

      {{-- 4 Easy Steps --}}
      <section class="section top-none"><div class="w-layout-blockcontainer container-default w-container"><div class="w-layout-grid grid-2-columns values-wrapper-grid"><div class="sticky-top static---tablet"><div class="inner-container _500px _100-tablet"><div class="inner-container _600px---tablet"><div class="mg-top-default"><h2 class="heading-8">4 Easy Steps</h2></div><div class="mg-top-small"><p class="paragraph-34">Our step-by-step process is designed to make replacing your windows and doors easy, stress-free, and fully tailored to your needs - from the first estimate to the final inspection.</p></div></div></div></div><div class="inner-container _592px _100-tablet"><div class="w-layout-grid grid-2-columns values-grid"><div class="value-wrapper"><div class="image-wrapper"><img src="/webflow-assets/images/684d86f32d344f16ce6ec364_flag_24dp_E3E3E3_FILL0_wght400_GRAD0_opsz24.svg" loading="eager" alt="For-architects-deluxe-windows" class="image"/></div><div class="mg-top-small"><h3 class="display-5 mid">Start</h3></div><div class="mg-top-extra-small"><p class="paragraph-5">Looking to replace your windows and doors? Reach out to Deluxe Windows for a complimentary estimate.</p></div></div><div class="value-wrapper"><div class="image-wrapper"><img src="/webflow-assets/images/684d86ff1fff20336f975d74_shopping_bag_24dp_E3E3E3_FILL0_wght400_GRAD0_opsz24.svg" loading="eager" alt="For-contractors-deluxe-windows" class="image"/></div><div class="mg-top-small"><h3 class="display-5 mid">Manufacture</h3></div><div class="mg-top-extra-small"><p class="paragraph-6">If you are satisfied with the provided estimate and approve it, we will order windows and doors according to your specifications and needs.</p></div></div><div class="value-wrapper"><div class="image-wrapper"><img src="/webflow-assets/images/684d870c533c4f729eb8094c_settings_24dp_E3E3E3_FILL0_wght400_GRAD0_opsz24.svg" loading="eager" alt="For-property-managers-owners-deluxe-windows" class="image"/></div><div class="mg-top-small"><h3 class="display-5 mid">Remove and install</h3></div><div class="mg-top-extra-small"><p class="paragraph-7">Once the products are ready, we will arrange a convenient time for installation and ensure your new windows and doors are expertly fitted.</p></div></div><div class="value-wrapper"><div class="image-wrapper"><img src="/webflow-assets/images/684d8718e99d2a34dfef7e4d_home_24dp_E3E3E3_FILL0_wght400_GRAD0_opsz24.svg" loading="eager" alt="For-property-managers-owners-deluxe-windows" class="image"/></div><div class="mg-top-small"><h3 class="display-5 mid">Final product</h3></div><div class="mg-top-extra-small"><p class="paragraph-7">Upon completion, each window and door will be thoroughly inspected to ensure they operate correctly and meet the highest standards of fit and finish.</p></div></div></div></div></div></div></section>

      {{-- CTA --}}
      <section class="section-card-wrapper"><div class="section-card cta-v3"><div class="w-layout-blockcontainer container-default w-container"><div class="w-layout-grid grid-2-columns cta-v3-grid"><div class="z-index-1"><div class="inner-container _500px---mbl"><div class="inner-container _480px"><div class="inner-container _450px"><div class="inner-container _300px---mbp"><div class="mg-top-small"><h2 class="heading-25">Your dream home starts here.</h2></div></div></div><div class="mg-top-small"><div class="text-neutral-light"><p class="paragraph-20">Tell us about your project - we'll take care of the rest.</p></div></div><div class="mg-top-default"><div class="buttons-row left"><a href="#" class="primary-button w-inline-block"><div class="text-block">Free Consultation</div></a></div></div></div></div></div><div class="image-wrapper cta-v3-image"><img src="/webflow-assets/images/687ca4b70b8583ef4890bad4_iPad.avif" loading="eager" alt="Deluxe-windows" class="image"/></div></div></div></div></section>

      {{-- FAQ --}}
      <section class="section top-none"><div class="w-layout-blockcontainer container-default w-container"><div class="w-layout-grid grid-2-columns faqs-grid-v3"><div class="sticky-top static---mbl"><div class="inner-container _450px---mbl"><div class="inner-container _275px---tablet _100-mbl"><div class="inner-container _340px _100-mbl"><div class="mg-top-small"><h2 class="heading-44">Do You Have Any Question?</h2></div><div class="div-block-49"><p class="paragraph-2">Call us at <a href="tel:855-355-0515">(650) 461-4446</a> to <br/>ask your questions. </p></div></div></div></div></div><div class="inner-container _763px width-100"><div class="card accordion-card v2"><div class="w-layout-grid grid-1-column accordion-v6"><div class="accordion-item-wrapper v2 first"><div class="accordion-top"><div class="text-titles"><h3 class="faqs-title">Which material is best for your windows?</h3></div><div class="accordion-icon-wrapper"><div class="accordion-icon-line vertical"></div><div class="accordion-icon-line"></div></div></div><div class="accordion-bottom v1"><p class="accordion-paragraph">The best window material depends on your home&#x27;s style, climate, energy efficiency needs, and budget. We offer a variety of options like vinyl, wood, aluminum, and fiberglass - each with its own benefits.</p></div></div><div data-w-id="faq-2" class="accordion-wrapper"><div class="accordion-item-wrapper v2"><div class="accordion-top"><div class="text-titles"><h3 class="faqs-title">Is consultation for free?</h3></div><div class="accordion-icon-wrapper"><div class="accordion-icon-line vertical"></div><div class="accordion-icon-line"></div></div></div><div class="accordion-bottom v1"><p class="accordion-paragraph">To get a free consultation, please fill out the form.</p></div></div></div><div data-w-id="faq-3" class="accordion-wrapper"><div class="accordion-item-wrapper v2"><div class="accordion-top"><div class="text-titles"><h3 class="faqs-title">When do I need new windows?</h3></div><div class="accordion-icon-wrapper"><div class="accordion-icon-line vertical"></div><div class="accordion-icon-line"></div></div></div><div class="accordion-bottom v1"><p class="accordion-paragraph">If you aren&#x27;t sure whether your windows need replacing, Deluxe Windows, Inc. can come to your home for a free consultation.</p></div></div></div><div data-w-id="faq-4" class="accordion-wrapper"><div class="accordion-item-wrapper v2 last"><div class="accordion-top"><div class="text-titles"><h3 class="faqs-title">How to choose windows brands and styles?</h3></div><div class="accordion-icon-wrapper"><div class="accordion-icon-line vertical"></div><div class="accordion-icon-line"></div></div></div><div class="accordion-bottom v1"><p class="accordion-paragraph">The answer to this question can only be answered once we come to your home for a free consultation. Every home is different, and when our professional window replacement specialist comes out to assess your house, we can factor in all the different aspects to suggest which product, style and price range will work best for you.</p></div></div></div></div></div></div></div></div></section>

      <section class="new-section"><div class="w-layout-blockcontainer container-default w-container"><div class="text-block-44">* Price applies to minimum window installation size of 24&quot;x24&quot;</div></div></section>

      @include('partials.footer')

    </div>{{-- end .page-wrapper --}}

    <div id="menuDimmer" style="opacity: 0; pointer-events: none"></div>

    <script src="/webflow-assets/js/jquery-3.5.1.min.js" type="text/javascript"></script>
    <script src="/webflow-assets/js/webflow-brands.js" type="text/javascript"></script>

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
  </body>
</html>

<!DOCTYPE html>
<html
  data-wf-domain="www.deluxewindows.com"
  data-wf-page="6841ddf8ace3d9d9facb15ce"
  data-wf-site="6841ddf8ace3d9d9facb14fd"
  lang="en"
  class="w-mod-js w-mod-ix"
>
  <head>
    <meta charset="utf-8" />
    <link href="https://cdn.prod.website-files.com" rel="preconnect" crossorigin="anonymous" />
    <title>{{ $seoTitle }}</title>
    <meta content="{{ $seoDescription }}" name="description" />
    <meta content="{{ $seoTitle }}" property="og:title" />
    <meta content="{{ $seoDescription }}" property="og:description" />
    <meta content="{{ $ogImage }}" property="og:image" />
    <meta content="{{ $seoTitle }}" name="twitter:title" />
    <meta content="{{ $seoDescription }}" name="twitter:description" />
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
        var n = c.documentElement, t = " w-mod-";
        n.className += t + "js";
        ("ontouchstart" in o || (o.DocumentTouch && c instanceof DocumentTouch)) && (n.className += t + "touch");
      })(window, document);
    </script>
    <link href="/webflow-assets/images/favicon.png" rel="shortcut icon" type="image/x-icon" />
    <link href="/webflow-assets/images/webclip-bg.png" rel="apple-touch-icon" />

    @include('partials.classic-layout-styles')

    <style>
      .w-webflow-badge { display: none !important; }
      .brand_dropdown-list { display: none; }
      .brand-filters_dropdown.is-open .brand_dropdown-list { display: block; }
    </style>

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

    <script async type="module" src="https://cdn.jsdelivr.net/npm/@finsweet/attributes@2/attributes.js" fs-list></script>
  </head>

  <body>
    <div class="page-wrapper full-height-page">

      @include('partials.navbar')

      <section class="section_breadcrumbs section-121">
        <div class="w-layout-blockcontainer container-default breadcrumbs-container w-container">
          <div class="breadcrumbs-wrapper">
            <a href="/" class="breadcrumb-link">Home</a>
            <div class="breadcrumb-div">/</div>
            <div class="breadcrumb-text">Brands</div>
          </div>
        </div>
      </section>

      <section class="section top-none">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="inner-container _600px center">
            <div class="center-content">
              <div class="mg-top-small is-vertical">
                <h1 class="heading-32">Trusted Brands We Work With</h1>
                <div class="text-size-16 text-color-dark-grey is-mob-centre">Explore our curated selection of top-tier window and door manufacturers to<br />find the perfect fit for your project.</div>
              </div>
            </div>
          </div>

          <div class="inner-container _600px center is-brands">
            <div class="center-content is-brand">
              <div class="w-form">
                <form
                  method="get"
                  name="wf-form-Brand-Filters"
                  fs-list-element="filters"
                  data-name="Brand Filters"
                  id="wf-form-Brand-Filters"
                  fs-list-conditionsmatch="or"
                  class="brand-filters_wrapper"
                  data-wf-page-id="6841ddf8ace3d9d9facb15ce"
                  data-wf-element-id="6e819cac-b5e6-9be0-1fb9-73484894156f"
                >
                  <div class="brand-filters_dropdown">
                    <div class="brand_dropdown-toggle">
                      <div class="filter-btn-txt">Materials</div>
                      <svg xmlns="http://www.w3.org/2000/svg" width="100%" viewBox="0 0 18 22" fill="none" class="filter-icon"><path d="M7.66797 12.1992L3.16797 7.69922L4.21797 6.64922L7.66797 10.0992L11.118 6.64922L12.168 7.69922L7.66797 12.1992Z" fill="#6B7280"></path></svg>
                    </div>
                    <div class="brand_dropdown-list">
                      @foreach(['Aluminum', 'Aluminum Clad', 'Fiberglass', 'Steel', 'Vinyl', 'Wood', 'Wood Clad'] as $materialLabel)
                      <label class="w-checkbox checkbox-field">
                        <div class="w-checkbox-input w-checkbox-input--inputType-custom brand-checkbox"></div>
                        <input type="checkbox" fs-list-field="materials" name="checkbox" data-name="Checkbox" style="opacity:0;position:absolute;z-index:-1" />
                        <span class="checkbox-label w-form-label" for="checkbox">{{ $materialLabel }}</span>
                      </label>
                      @endforeach
                    </div>
                  </div>

                  <div class="brand-filters_dropdown">
                    <div class="brand_dropdown-toggle">
                      <div class="filter-btn-txt">Price Range</div>
                      <svg xmlns="http://www.w3.org/2000/svg" width="100%" viewBox="0 0 18 22" fill="none" class="filter-icon"><path d="M7.66797 12.1992L3.16797 7.69922L4.21797 6.64922L7.66797 10.0992L11.118 6.64922L12.168 7.69922L7.66797 12.1992Z" fill="#6B7280"></path></svg>
                    </div>
                    <div class="brand_dropdown-list is-mob-130">
                      @foreach(['price1' => '$', 'price2' => '$$', 'price3' => '$$$', 'price4' => '$$$$', 'price5' => '$$$$$'] as $priceField => $priceLabel)
                      <label class="w-checkbox checkbox-field">
                        <div class="w-checkbox-input w-checkbox-input--inputType-custom brand-checkbox"></div>
                        <input type="checkbox" fs-list-field="{{ $priceField }}" name="checkbox" data-name="Checkbox" style="opacity:0;position:absolute;z-index:-1" />
                        <span class="checkbox-label w-form-label" for="checkbox">{{ $priceLabel }}</span>
                      </label>
                      @endforeach
                    </div>
                  </div>

                  <a fs-list-element="clear" href="#" class="text-size-14 is-link">Clear all</a>
                </form>
                <div class="w-form-done"><div>Thank you! Your submission has been received!</div></div>
                <div class="w-form-fail"><div>Oops! Something went wrong while submitting the form.</div></div>
              </div>
            </div>
          </div>

          <div class="mg-top-large">
            <div class="collection-list-wrapper-5 w-dyn-list">
              <div fs-list-element="list" role="list" class="collection-list-2 brands-list w-dyn-items">
                @foreach($brands as $brand)
                  @include('partials.brand-index-card', ['brand' => $brand])
                @endforeach
              </div>
            </div>
          </div>
        </div>
      </section>

      <section id="tab4" class="section_cta-small">
        <div class="section_white-2 transparent margitn-top-40">
          <div class="container-default-7">
            <div class="section_wrapper-2 center-align transparent gap16">
              <div class="text-size-16 text-align-centre">Not sure which brand is right for you?</div>
              <a href="/contacts" class="primary-button-6 sidebar-button w-inline-block">
                <div class="text-block">Get a free consultation</div>
              </a>
            </div>
          </div>
        </div>
      </section>

      @include('partials.footer')
    </div>

    <script src="/webflow-assets/js/jquery-3.5.1.min.js" type="text/javascript"></script>
    <script src="/webflow-assets/js/webflow.js" type="text/javascript"></script>
    <script src="/webflow-assets/js/webflow-brand-index.js" type="text/javascript"></script>

    <script>
      (function () {
        const TRACK_PARAMS = [
          "utm_source","utm_medium","utm_campaign",
          "utm_term","utm_content","matchtype",
          "device","creative","gclid"
        ];
        const params = new URLSearchParams(window.location.search);
        const hasUtm = TRACK_PARAMS.some(p => params.get(p));
        if (hasUtm) {
          TRACK_PARAMS.forEach(param => {
            const value = params.get(param);
            if (value) localStorage.setItem(`lead_param_${param}`, value);
            else localStorage.removeItem(`lead_param_${param}`);
          });
        } else {
          const hasSavedUtm = TRACK_PARAMS.some(p => localStorage.getItem(`lead_param_${p}`));
          if (!hasSavedUtm) {
            const ref = document.referrer || "";
            let searchEngine = "", organicKeyword = "", refDomain = "";
            try {
              if (ref) {
                const refUrl = new URL(ref);
                refDomain = refUrl.hostname.replace(/^www\./, "");
                const SEO_ENGINES = {
                  "google.com": "google", "bing.com": "bing", "yahoo.com": "yahoo",
                  "duckduckgo.com": "duckduckgo", "yandex.ru": "yandex", "yandex.com": "yandex", "baidu.com": "baidu"
                };
                for (const [domain, name] of Object.entries(SEO_ENGINES)) {
                  if (refDomain.includes(domain)) {
                    searchEngine = name;
                    organicKeyword = refUrl.searchParams.get("q") || refUrl.searchParams.get("p") || refUrl.searchParams.get("query") || "(not provided)";
                    break;
                  }
                }
              }
            } catch (e) {}
            if (searchEngine) {
              localStorage.setItem("lead_param_utm_source", searchEngine);
              localStorage.setItem("lead_param_utm_medium", "organic");
              if (organicKeyword) localStorage.setItem("lead_param_utm_term", organicKeyword);
            } else if (refDomain && !refDomain.includes(window.location.hostname)) {
              localStorage.setItem("lead_param_utm_source", refDomain);
              localStorage.setItem("lead_param_utm_medium", "referral");
            } else {
              localStorage.setItem("lead_param_utm_source", "(direct)");
              localStorage.setItem("lead_param_utm_medium", "(none)");
            }
          }
        }
        if (!localStorage.getItem("lead_param_landing_page")) {
          localStorage.setItem("lead_param_landing_page", window.location.pathname);
        }
        function injectHiddenFields(form) {
          [...TRACK_PARAMS, "landing_page"].forEach(param => {
            if (!form.querySelector(`input[name="${param}"]`)) {
              const input = document.createElement("input");
              input.type = "hidden";
              input.name = param;
              input.value = localStorage.getItem(`lead_param_${param}`) || "";
              form.appendChild(input);
            }
          });
        }
        document.addEventListener("DOMContentLoaded", function () {
          document.querySelectorAll("form").forEach(injectHiddenFields);
        });
        let lazyLoaded = false;
        function initLazy() {
          if (lazyLoaded) return;
          lazyLoaded = true;
          loadZoho();
        }
        window.addEventListener("scroll", initLazy, { once: true });
        window.addEventListener("click", initLazy, { once: true });
        setTimeout(initLazy, 4000);
        function loadZoho() {
          const script = document.createElement("script");
          script.src = "https://salesiq.zohopublic.com/widget?wc=siqfe34762ee44eb77120f2a13c55fed7c0984ca603ae60aafcaf2adda4331dc65a";
          script.defer = true;
          document.body.appendChild(script);
        }
      })();
    </script>
  </body>
</html>

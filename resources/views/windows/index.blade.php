<!DOCTYPE html>
<html
  data-wf-domain="www.deluxewindows.com"
  data-wf-page="6841ddf8ace3d9d9facb15cd"
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
    <meta content="{{ $ogImage }}" name="twitter:image" />
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
  </head>

  <body class="body-18 height-auto">
    <div class="page-wrapper">

      @include('partials.navbar')
      @include('partials.header-scripts')

      <section class="section_breadcrumbs section-121">
        <div class="w-layout-blockcontainer container-default breadcrumbs-container w-container">
          <div class="breadcrumbs-wrapper">
            <a href="/" class="breadcrumb-link">Home</a>
            <div class="breadcrumb-div">/</div>
            <div class="breadcrumb-text">Windows</div>
          </div>
        </div>
      </section>

      <section class="section hero-v4">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="w-layout-vflex inner-container _500px---mbl center">
            <div class="mg-top-small">
              <h1 class="display-10 mid text-light">High-Quality Windows <br />for Your Home</h1>
            </div>
          </div>
          <div class="mg-top-small">
            <div class="inner-container _562px center">
              <div class="text-neutral-light">
                <p class="paragraph-26">Upgrade your home with energy-efficient, durable, and stylish windows. Explore a full range of replacement and custom windows made for American homes.</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="section">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="collection-list-wrapper-2 w-dyn-list">
            <div role="list" class="grid-2-columns properties-grid---v1 w-dyn-items">
              @foreach($windows as $window)
                @include('partials.windows-index-card', ['window' => $window])
              @endforeach
            </div>
          </div>
        </div>
      </section>

      @include('partials.footer')
    </div>

    <div id="menuDimmer" style="opacity: 0; pointer-events: none"></div>

    <script src="/webflow-assets/js/jquery-3.5.1.min.js" type="text/javascript"></script>
    <script src="/webflow-assets/js/webflow.js" type="text/javascript"></script>

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

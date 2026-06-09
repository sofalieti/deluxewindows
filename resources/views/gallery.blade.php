<!DOCTYPE html>
<html
  data-wf-domain="www.deluxewindows.com"
  data-wf-page="gallery"
  data-wf-site="6841ddf8ace3d9d9facb14fd"
  lang="en"
  class="w-mod-js w-mod-ix"
>
  <head>
    <meta charset="utf-8" />
    <link href="https://cdn.prod.website-files.com" rel="preconnect" crossorigin="anonymous" />
    <title>Photo Gallery | Deluxe Windows – Bay Area</title>
    <meta content="Browse our photo gallery of completed window and door replacement projects across the Bay Area by Deluxe Windows." name="description" />
    <meta content="Photo Gallery | Deluxe Windows – Bay Area" property="og:title" />
    <meta content="Browse our photo gallery of completed window and door replacement projects across the Bay Area by Deluxe Windows." property="og:description" />
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
      .dw-gallery-grid {
        columns: 4;
        column-gap: 8px;
      }
      .dw-gallery-item {
        break-inside: avoid;
        display: block;
        margin-bottom: 8px;
        border-radius: 10px;
        overflow: hidden;
        background: #e8edf2;
        position: relative;
      }
      .dw-gallery-item img {
        width: 100%;
        display: block;
        transition: transform 0.35s ease;
      }
      .dw-gallery-item::after {
        content: '';
        position: absolute;
        inset: 0;
        background: rgba(15, 42, 72, 0);
        transition: background 0.25s ease;
        pointer-events: none;
      }
      .dw-gallery-item:hover img {
        transform: scale(1.04);
      }
      .dw-gallery-item:hover::after {
        background: rgba(15, 42, 72, 0.1);
      }
      .gallery-intro-note {
        font-size: 0.88rem;
        color: #7a8fa6;
        margin-top: 10px;
        font-style: italic;
      }
      @media (max-width: 991px) {
        .dw-gallery-grid { columns: 3; }
      }
      @media (max-width: 640px) {
        .dw-gallery-grid { columns: 2; column-gap: 6px; }
        .dw-gallery-item { margin-bottom: 6px; border-radius: 7px; }
      }
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
    <link href="https://core.service.elfsight.com/" rel="preconnect" crossorigin="" />
  </head>

  <body class="body-18 height-auto gallery-page">
    <div class="page-wrapper">

      @include('partials.navbar')
      @include('partials.header-scripts')

      <section class="section_breadcrumbs section-121">
        <div class="w-layout-blockcontainer container-default breadcrumbs-container w-container">
          <div class="breadcrumbs-wrapper">
            <a href="/" class="breadcrumb-link">Home</a>
            <div class="breadcrumb-div">/</div>
            <div class="breadcrumb-text">Photo Gallery</div>
          </div>
        </div>
      </section>

      <section class="section pd-top-80px top-none">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="mg-top-extra-large">
            <div class="title-left---content-right">
              <h1 class="display-9 mid">Photo Gallery</h1>
            </div>
            <div class="mg-top-small">
              <p class="paragraph-20">
                Browse through our photo gallery showcasing Deluxe Windows' windows and doors projects to spark your imagination.
              </p>
              <p class="gallery-intro-note">
                Some projects were completed during previous ownership. All photos taken by Felix, highlighting his professional experience.
              </p>
            </div>
          </div>
        </div>
      </section>

      <section class="section" style="padding-top:32px;">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="dw-gallery-grid">
            @foreach($images as $image)
              <a href="{{ $image }}" target="_blank" class="dw-gallery-item" rel="noopener noreferrer">
                <img src="{{ $image }}" alt="Deluxe Windows project" loading="lazy" />
              </a>
            @endforeach
          </div>
        </div>
      </section>

      @include('partials.cta')
      @include('partials.footer')

    </div>

    <div id="menuDimmer" style="opacity: 0; pointer-events: none"></div>

    @include('partials.classic-site-scripts')

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

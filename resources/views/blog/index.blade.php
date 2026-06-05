<!DOCTYPE html>
<html
  data-wf-domain="www.deluxewindows.com"
  data-wf-page="6841df5688ca2f74fd53ec90"
  data-wf-site="6841ddf8ace3d9d9facb14fd"
  lang="en"
  class="w-mod-js w-mod-ix"
>
  <head>
    <meta charset="utf-8" />
    <link href="https://cdn.prod.website-files.com" rel="preconnect" crossorigin="anonymous" />
    <title>Window Tips &amp; Design Blog | Deluxe Windows – Bay Area</title>
    <meta content="Expert window tips, buying guides, and design inspiration for Bay Area homeowners from Deluxe Windows." name="description" />
    <meta content="Window Tips &amp; Design Blog | Deluxe Windows – Bay Area" property="og:title" />
    <meta content="Expert window tips, buying guides, and design inspiration for Bay Area homeowners from Deluxe Windows." property="og:description" />
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

    @include('partials.classic-layout-styles')

    <style>
      .blog-index-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 28px;
      }
      .blog-index-card {
        background: #fff;
        border-radius: 14px;
        overflow: hidden;
        text-decoration: none;
        color: inherit;
        box-shadow: 0 2px 14px rgba(0,0,0,0.07);
        transition: box-shadow 0.2s, transform 0.2s;
        display: flex;
        flex-direction: column;
      }
      .blog-index-card:hover {
        box-shadow: 0 8px 28px rgba(0,0,0,0.13);
        transform: translateY(-2px);
      }
      .blog-index-card-img {
        width: 100%;
        aspect-ratio: 16/9;
        object-fit: cover;
        display: block;
        background: #e8edf2;
      }
      .blog-index-card-body {
        padding: 20px 22px 24px;
        flex: 1;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
      }
      .blog-index-card-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: #1a1a1a;
        line-height: 1.45;
        margin-bottom: 14px;
      }
      .blog-index-card-date {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.82rem;
        color: #888;
      }
      .blog-index-card-date svg {
        flex-shrink: 0;
        opacity: 0.6;
      }

      @media (max-width: 900px) {
        .blog-index-grid { grid-template-columns: repeat(2, 1fr); }
      }
      @media (max-width: 540px) {
        .blog-index-grid { grid-template-columns: 1fr; }
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

  <body class="body-18 height-auto blog-page">
    <div class="page-wrapper">

      @include('partials.navbar')
      @include('partials.header-scripts')

      @include('partials.trust-badges')

      <section class="section_breadcrumbs section-121">
        <div class="w-layout-blockcontainer container-default breadcrumbs-container w-container">
          <div class="breadcrumbs-wrapper">
            <a href="/" class="breadcrumb-link">Home</a>
            <div class="breadcrumb-div">/</div>
            <div class="breadcrumb-text">Knowledge Articles</div>
          </div>
        </div>
      </section>

      <section class="section pd-120px top-none">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="mg-top-extra-large">
            <div class="title-left---content-right">
              <h1 class="display-9 mid">Knowledge Articles</h1>
            </div>
            <div class="mg-top-large">
              <div class="blog-index-grid">
                @foreach($posts as $post)
                <a href="/blog/{{ $post['slug'] }}" class="blog-index-card">
                  @if($post['image'])
                  <img
                    class="blog-index-card-img"
                    src="{{ $post['image'] }}"
                    alt="{{ $post['name'] }}"
                    loading="lazy"
                  />
                  @else
                  <div class="blog-index-card-img"></div>
                  @endif
                  <div class="blog-index-card-body">
                    <div class="blog-index-card-title">{{ $post['name'] }}</div>
                    @if($post['published'])
                    <div class="blog-index-card-date">
                      <svg width="14" height="14" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="1" y="2" width="14" height="13" rx="2" stroke="currentColor" stroke-width="1.5"/>
                        <path d="M1 6h14" stroke="currentColor" stroke-width="1.5"/>
                        <path d="M5 1v2M11 1v2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                      </svg>
                      {{ $post['published'] }}
                    </div>
                    @endif
                  </div>
                </a>
                @endforeach
              </div>
            </div>
          </div>
        </div>
      </section>

      @include('partials.footer')

    </div>{{-- end .page-wrapper --}}

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

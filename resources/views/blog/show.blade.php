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
    <title>{{ $seoTitle }} | Deluxe Windows Blog</title>
    <meta content="{{ $seoDescription }}" name="description" />
    <meta content="{{ $ogTitle }}" property="og:title" />
    <meta content="{{ $ogDescription }}" property="og:description" />
    @if($heroImage)
    <meta content="{{ $heroImage }}" property="og:image" />
    @endif
    <meta content="{{ $ogTitle }}" name="twitter:title" />
    <meta content="{{ $ogDescription }}" name="twitter:description" />
    @if($heroImage)
    <meta content="{{ $heroImage }}" name="twitter:image" />
    @endif
    <meta property="og:type" content="article" />
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
      .blog-post-body p,
      .blog-post-body li {
        font-size: 1.05rem;
        line-height: 1.8;
        color: #333;
      }
      .blog-post-body p { margin-bottom: 20px; }
      .blog-post-body h2 {
        font-size: 1.7rem;
        font-weight: 700;
        color: #1a1a1a;
        margin-top: 48px;
        margin-bottom: 16px;
      }
      .blog-post-body h3,
      .blog-post-body h4 {
        font-size: 1.25rem;
        font-weight: 700;
        color: #1E73B9;
        margin-top: 32px;
        margin-bottom: 12px;
      }
      .blog-post-body h5 {
        font-size: 1.1rem;
        font-weight: 700;
        color: #1a1a1a;
        margin-top: 24px;
        margin-bottom: 10px;
      }
      .blog-post-body ol,
      .blog-post-body ul { padding-left: 24px; margin-bottom: 20px; }
      .blog-post-body li { margin-bottom: 8px; }
      .blog-post-body img {
        width: 100%;
        border-radius: 10px;
        margin: 32px 0;
        display: block;
      }

      .blog-related-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 24px;
      }
      .blog-related-card {
        background: #fff;
        border-radius: 12px;
        overflow: hidden;
        text-decoration: none;
        color: inherit;
        box-shadow: 0 2px 12px rgba(0,0,0,0.07);
        transition: box-shadow 0.2s;
        display: flex;
        flex-direction: column;
      }
      .blog-related-card:hover { box-shadow: 0 6px 24px rgba(0,0,0,0.13); }
      .blog-related-card img {
        width: 100%;
        aspect-ratio: 16/9;
        object-fit: cover;
      }
      .blog-related-card-body { padding: 20px; }
      .blog-related-card-title {
        font-size: 1rem;
        font-weight: 700;
        color: #1a1a1a;
        line-height: 1.4;
      }

      @media (max-width: 900px) {
        .blog-related-grid { grid-template-columns: 1fr; }
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
            <a href="/blog" class="breadcrumb-link hidden-link">Blog</a>
            <div class="breadcrumb-div hidden-txt">/</div>
            <div class="breadcrumb-text">{{ $title }}</div>
          </div>
        </div>
      </section>

      <section class="section pd-120px top-none">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="mg-top-extra-large">
            <div class="inner-container _800px _100-tablet">
              <h1 class="display-8 mid">{{ $title }}</h1>

              @if($heroImage)
              <div class="mg-top-default">
                <div class="image-wrapper border-radius-image-default">
                  <img
                    src="{{ $heroImage }}"
                    loading="eager"
                    alt="{{ $title }}"
                    class="image cover-image"
                  />
                </div>
              </div>
              @endif

              @if($bodyHtml)
              <div class="mg-top-default">
                <div class="rich-text-v2 w-richtext blog-post-body">
                  {!! $bodyHtml !!}
                </div>
              </div>
              @endif
            </div>
          </div>
        </div>
      </section>

      @if($relatedPosts->count() > 0)
      <section class="section top-none">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="title-left---content-right">
            <h2 class="heading-23">Read More Articles</h2>
          </div>
          <div class="mg-top-large">
            <div class="blog-related-grid">
              @foreach($relatedPosts as $post)
              <a href="/blog/{{ $post['slug'] }}" class="blog-related-card">
                @if($post['image'])
                <img src="{{ $post['image'] }}" alt="{{ $post['name'] }}" loading="lazy" />
                @endif
                <div class="blog-related-card-body">
                  <div class="blog-related-card-title">{{ $post['name'] }}</div>
                </div>
              </a>
              @endforeach
            </div>
          </div>
        </div>
      </section>
      @endif

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

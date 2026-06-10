<!DOCTYPE html>
<html
  data-wf-domain="www.deluxewindows.com"
  data-wf-page="687b79c6ee572b31129b17c3"
  data-wf-site="6841ddf8ace3d9d9facb14fd"
  lang="en"
  data-wf-collection="687b79c5ee572b31129b17bf"
  data-wf-item-slug="{{ $slug }}"
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
      @include('partials.header-scripts')

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

      <section class="section hero-v4">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="blog-post-single---top-content">
            <div class="inner-container _770px">
              <div class="inner-container _550px---tablet">
                <div class="mg-top-small">
                  <h1 class="display-9 mid">{{ $title }}</h1>
                </div>
              </div>
            </div>
          </div>

          @if($heroImage)
          <div class="mg-top-extra-large">
            <div class="image-wrapper border-radius-image-default">
              <img
                src="{{ $heroImage }}"
                loading="eager"
                alt="{{ $title }}"
                class="image post---featured-image"
              />
            </div>
          </div>
          @endif

          @if($bodyHtml)
          <div class="mg-top-section-large">
            <div class="inner-container _690px center">
              <div class="rich-text-v1 mg-bottom--16px w-richtext">
                {!! $bodyHtml !!}
              </div>
            </div>
          </div>
          @endif
        </div>
      </section>

      @if($relatedPosts->count() > 0)
      <section class="section pd-120px">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="title-left---content-right">
            <div class="width-100-mobile-portrait">
              <h2 class="display-8 mid">Read More Articles</h2>
            </div>
          </div>
          <div class="mg-top-large">
            <div class="w-dyn-list">
              <div role="list" class="collection-list-8 w-dyn-items">
                @foreach($relatedPosts as $post)
                  @include('partials.blog-index-card', [
                    'post' => $post,
                    'loading' => $loop->first ? 'eager' : 'lazy',
                  ])
                @endforeach
              </div>
            </div>
          </div>
        </div>
      </section>
      @endif

      @include('partials.blog-page-bottom', [
        'wfPageId' => '687b79c6ee572b31129b17c3',
        'ctaHeadingClass' => 'heading-43',
        'contactHeadingClass' => 'heading-34',
      ])

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

<!DOCTYPE html>
<html
  data-wf-domain="www.deluxewindows.com"
  data-wf-page="688097fa174129b5ec241dd4"
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

    <link href="https://core.service.elfsight.com/" rel="preconnect" crossorigin="" />
  </head>

  <body class="body-18 height-auto blog-page">
    <div class="page-wrapper">

      @include('partials.navbar')
      @include('partials.header-scripts')

      <section class="section_breadcrumbs section-121">
        <div class="w-layout-blockcontainer container-default breadcrumbs-container w-container">
          <div class="breadcrumbs-wrapper">
            <a href="/" class="breadcrumb-link">Home</a>
            <div class="breadcrumb-div">/</div>
            <div class="breadcrumb-text">Blog</div>
          </div>
        </div>
      </section>

      <section class="section pd-120px">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="inner-container _600px center">
            <div class="text-center">
              <div class="inner-container _500px---mbl center">
                <h1 class="display-9 mid">Knowledge Articles<br /></h1>
              </div>
            </div>
          </div>
        </div>
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="mg-top-large">
            <div class="collection-list-wrapper-8 w-dyn-list">
              <div role="list" class="collection-list-5 w-dyn-items">
                @foreach($posts as $post)
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

      @include('partials.blog-page-bottom')

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

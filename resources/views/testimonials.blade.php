<!DOCTYPE html>
<html
  data-wf-domain="www.deluxewindows.com"
  data-wf-page="687a8de5e8e76e587d2190ad"
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
    <link href="https://static.elfsight.com/" rel="preconnect" crossorigin="anonymous" />
    <link href="https://core.service.elfsight.com/" rel="preconnect" crossorigin="" />

    @include('partials.classic-layout-styles')

    <style>
      .w-webflow-badge { display: none !important; }
    </style>

  </head>

  <body>
    <div class="page-wrapper">

      @include('partials.navbar')

      <section class="section hero-v4">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="inner-container _600px center">
            <div class="text-center">
              <div class="inner-container _500px---mbl center">
                <h1 class="display-9 mid">Look at <br/>What People Say <span class="text-no-wrap">About Us</span><br/></h1>
              </div>
            </div>
          </div>
        </div>
      </section>

      <div class="w-embed w-script">
        <!-- Elfsight Yelp Reviews | Untitled Yelp Reviews -->
        <script src="https://static.elfsight.com/platform/platform.js" async></script>
        <div class="elfsight-app-9b5ea9e5-b8e2-46ee-a99c-1e6552b85f66" data-elfsight-app-lazy></div>
      </div>

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

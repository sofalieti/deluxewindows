<!DOCTYPE html>
<html
  data-wf-domain="www.deluxewindows.com"
  data-wf-page="69ce7898d019bc268b4bb9e4"
  data-wf-site="6841ddf8ace3d9d9facb14fd"
  lang="en"
  data-wf-collection="69ce7898d019bc268b4bb9ca"
  data-wf-item-slug="{{ $slug }}"
  class="w-mod-js w-mod-ix"
>
  <head>
    <meta charset="utf-8" />
    <link href="https://cdn.prod.website-files.com" rel="preconnect" crossorigin="anonymous" />
    <title>{{ $metaTitle }}</title>
    <meta content="{{ $metaDescription }}" name="description" />
    <meta content="{{ $metaTitle }}" property="og:title" />
    <meta content="{{ $metaDescription }}" property="og:description" />
    @if($heroImage)
    <meta content="{{ $heroImage }}" property="og:image" />
    @endif
    <meta content="{{ $metaTitle }}" name="twitter:title" />
    <meta content="{{ $metaDescription }}" name="twitter:description" />
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

    @foreach($schemaScripts as $schemaJson)
    <script type="application/ld+json">{!! $schemaJson !!}</script>
    @endforeach

    @include('partials.classic-layout-styles')

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

  <body class="body-19">
    @include('partials.navbar')
    @include('partials.header-scripts')

    @include('partials.service-area-hero', [
      'cityName' => $cityName,
      'cityLabel' => $cityLabel,
      'heroImage' => $heroImage,
    ])

    @include('partials.trust-badges')

    @include('partials.service-area-main', [
      'cityName' => $cityName,
      'cityLabel' => $cityLabel,
      'countyName' => $countyName,
      'countyHubSlug' => $countyHubSlug,
      'paragraph1' => $paragraph1,
      'paragraph2' => $paragraph2,
    ])

    @include('partials.service-area-window-types', [
      'cityName' => $cityName,
      'windowTypes' => $windowTypes,
    ])

    @include('partials.service-area-brands', [
      'cityName' => $cityName,
      'featuredBrands' => $featuredBrands,
    ])

    @include('partials.county-hub-pricing', ['pricingTitle' => $cityName])

    @include('partials.service-area-process')

    @include('partials.service-area-why', [
      'cityName' => $cityName,
      'countyName' => $countyName,
    ])

    @include('partials.guarantee')

    <section>
      <div class="w-layout-blockcontainer container-default w-container">
        <div class="w-embed w-script">
          <script src="https://elfsightcdn.com/platform.js" async></script>
          <div class="elfsight-app-54d8cb68-4afb-4ebe-b139-2bd0bc687876" data-elfsight-app-lazy></div>
        </div>
      </div>
    </section>

    @include('partials.service-area-faq', ['faqs' => $faqs])

    @include('partials.county-hub-bottom-cta', ['ctaLocationLabel' => $cityName])

    @include('partials.footer')

    <div id="menuDimmer" style="opacity: 0; pointer-events: none"></div>

    @include('partials.classic-site-scripts')
  </body>
</html>

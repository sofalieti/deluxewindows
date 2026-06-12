<!DOCTYPE html>
<html
  data-wf-domain="www.deluxewindows.com"
  data-wf-page="6841ddf8ace3d9d9facb1638"
  data-wf-site="6841ddf8ace3d9d9facb14fd"
  lang="en"
  class="w-mod-js w-mod-ix"
>
  <head>
    <meta charset="utf-8" />
    <link href="https://cdn.prod.website-files.com" rel="preconnect" crossorigin="anonymous" />
    <title>Contact Deluxe Windows | Bay Area Window Experts</title>
    <meta content="Get in touch with Deluxe Windows in San Francisco. Call, email, or request a free quote for window and door installation across the Bay Area. We respond fast." name="description" />
    <meta content="Contact Deluxe Windows | Bay Area Window Experts" property="og:title" />
    <meta content="Get in touch with Deluxe Windows in San Francisco. Call, email, or request a free quote for window and door installation across the Bay Area. We respond fast." property="og:description" />
    <meta content="https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb14fd/684da952cef202b8dda5788c_Meta%20cover-2.jpg" property="og:image" />
    <meta content="Contact Deluxe Windows | Bay Area Window Experts" name="twitter:title" />
    <meta content="Get in touch with Deluxe Windows in San Francisco. Call, email, or request a free quote for window and door installation across the Bay Area. We respond fast." name="twitter:description" />
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

    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "HomeAndConstructionBusiness",
      "name": "Deluxe Windows",
      "url": "https://www.deluxewindows.com",
      "telephone": "{{ site_phone_tel() }}",
      "description": "Premium window and door replacement for San Francisco Bay Area homes. 30+ years, 100% employee owned.",
      "priceRange": "$$",
      "aggregateRating": {
        "@type": "AggregateRating",
        "ratingValue": "4.9",
        "reviewCount": "231",
        "bestRating": "5"
      },
      "openingHoursSpecification": [
        {
          "@type": "OpeningHoursSpecification",
          "dayOfWeek": ["Monday","Tuesday","Wednesday","Thursday","Friday"],
          "opens": "08:00",
          "closes": "18:00"
        },
        {
          "@type": "OpeningHoursSpecification",
          "dayOfWeek": "Saturday",
          "opens": "09:00",
          "closes": "15:00"
        }
      ],
      "areaServed": {
        "@type": "GeoCircle",
        "geoMidpoint": {
          "@type": "GeoCoordinates",
          "latitude": 37.5630,
          "longitude": -122.0329
        },
        "geoRadius": "100000"
      }
    }
    </script>

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
    <script>window.$zoho = window.$zoho || {}; $zoho.salesiq = $zoho.salesiq || { ready: function () {} };</script>
    <link href="https://core.service.elfsight.com/" rel="preconnect" crossorigin="" />
  </head>

  <body>
    <div class="page-wrapper">
      @include('partials.navbar')
      @include('partials.header-scripts')

      @include('partials.contacts-webflow-section')

      @include('partials.faq', [
        'sectionExtraClass' => ' top-none',
        'faqFormHref' => '#wf-form-Contact-V1-Form',
      ])

      @include('partials.footer')
    </div>

    <div id="menuDimmer" style="opacity: 0; pointer-events: none"></div>

    @include('partials.classic-site-scripts')
  </body>
</html>

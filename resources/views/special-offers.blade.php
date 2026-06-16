@php
  $cdn = 'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb14fd';
  $calendarIcon = '/webflow-assets/images/6841ddf8ace3d9d9facb1894_calendar-icon-property-x-webflow-template.svg';
@endphp
<!DOCTYPE html>
<html
  data-wf-domain="www.deluxewindows.com"
  data-wf-page="687a4292617b9b4ed5cfe680"
  data-wf-site="6841ddf8ace3d9d9facb14fd"
  lang="en"
  class="w-mod-js w-mod-ix"
>
  <head>
    <meta charset="utf-8" />
    <link href="https://cdn.prod.website-files.com" rel="preconnect" crossorigin="anonymous" />
    <title>Window Replacement Deals | Special Offers – Deluxe Windows</title>
    <meta content="Save on premium window replacement in San Francisco. Explore Deluxe Windows' latest seasonal discounts, limited-time promotions, and special financing offers." name="description" />
    <meta content="Window Replacement Deals | Special Offers – Deluxe Windows" property="og:title" />
    <meta content="Save on premium window replacement in San Francisco. Explore Deluxe Windows' latest seasonal discounts, limited-time promotions, and special financing offers." property="og:description" />
    <meta content="{{ $cdn }}/684da952cef202b8dda5788c_Meta%20cover-2.jpg" property="og:image" />
    <meta content="Window Replacement Deals | Special Offers – Deluxe Windows" name="twitter:title" />
    <meta content="Save on premium window replacement in San Francisco. Explore Deluxe Windows' latest seasonal discounts, limited-time promotions, and special financing offers." name="twitter:description" />
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

      <section class="section_breadcrumbs section-121">
        <div class="w-layout-blockcontainer container-default breadcrumbs-container w-container">
          <div class="breadcrumbs-wrapper">
            <a href="/" class="breadcrumb-link">Home</a>
            <div class="breadcrumb-div">/</div>
            <div class="breadcrumb-text">Special offers</div>
          </div>
        </div>
      </section>

      <section class="section hero-v8">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="inner-container _600px center">
            <div class="text-center">
              <div class="inner-container _500px---mbl center">
                <h1 class="display-9 mid">Limited-Time Window &amp; Doors Replacement Offers<br /></h1>
              </div>
            </div>
          </div>
        </div>
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="mg-top-large mg-top-40px---mbl">
            <div class="w-dyn-list">
              <div role="list" class="grid-1-column featured-blog-grid-v3 w-dyn-items">
                @foreach($coupons as $coupon)
                <div role="listitem" class="featured-blog-card-v3 w-dyn-item">
                  @if($coupon['image'] !== '')
                  <a href="#email-form-2" class="image-wrapper featured-blog-v3 w-inline-block">
                    <img src="{{ $coupon['image'] }}" loading="eager" alt="" class="image cover-image" />
                  </a>
                  @endif
                  <a href="#email-form-2" class="card featured-card-blog-v3 w-inline-block">
                    <div>
                      <div class="card-post-date">
                        <img src="{{ $calendarIcon }}" loading="eager" alt="Calendary Icon - Property X Webflow Template" />
                        <div class="text-neutral-light"><div>{{ $coupon['expires_label'] }}</div></div>
                      </div>
                    </div>
                    <div class="inner-container _450px---mbl">
                      <div class="mg-top-default"><h2 class="display-6">{{ $coupon['name'] }}</h2></div>
                      <div class="mg-top-small">
                        <p class="paragraph-50">{{ $coupon['description'] }}</p>
                      </div>
                    </div>
                  </a>
                </div>
                @endforeach
              </div>
            </div>
          </div>
        </div>
      </section>

      @include('partials.special-offers-contact-section')

      <section class="section-card-wrapper cta-v3"></section>

      @include('partials.footer')
    </div>

    <div id="menuDimmer" style="opacity: 0; pointer-events: none"></div>

    @include('partials.classic-site-scripts')
  </body>
</html>

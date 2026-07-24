<!DOCTYPE html>
<html
  data-wf-domain="www.deluxewindows.com"
  data-wf-page="@yield('wfPage', '6841df5688ca2f74fd53ec90')"
  data-wf-site="6841ddf8ace3d9d9facb14fd"
  @hasSection('wfCollection') data-wf-collection="@yield('wfCollection')" @endif
  @hasSection('wfItemSlug') data-wf-item-slug="@yield('wfItemSlug')" @endif
  lang="en"
  class="@yield('htmlClass', 'w-mod-js w-mod-ix')"
>
  <head>
    <meta charset="utf-8" />
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-JHYBB0THJM"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag() { dataLayer.push(arguments); }
      gtag('js', new Date());
      gtag('config', 'G-JHYBB0THJM');
      gtag('config', 'AW-1030787786');

      function gtag_report_conversion(url) {
        var callback = function () {
          if (typeof url !== 'undefined') {
            window.location = url;
          }
        };
        gtag('event', 'conversion', {
          'send_to': 'AW-1030787786/Hs9eCP7MwngQyqXC6wM',
          'event_callback': callback
        });
        return false;
      }
    </script>
    @include('partials.seo-head')
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <meta name="theme-color" content="#0f4d89" />
    <link href="{{ site_css_bundle_url([
      'webflow-assets/css/webflow.min.css',
      'webflow-assets/css/fonts.css',
      'webflow-assets/css/promo-offer.css',
      'webflow-overrides/site-custom.css',
    ]) }}" rel="stylesheet" type="text/css" />
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

    @yield('head')

    <link href="https://core.service.elfsight.com/" rel="preconnect" crossorigin="" />
  </head>

  <body class="classic-public-page @yield('bodyClass')">
    <div class="page-wrapper @yield('pageWrapperClass')">
      @include('partials.navbar')

      @yield('content')

      @hasSection('metadataFaqRendered')
      @else
        @include('partials.page-metadata-faq')
      @endif

      @include('partials.footer')
    </div>

    <div id="menuDimmer"></div>

    @hasSection('bodyScripts')
      @yield('bodyScripts')
    @else
      @include('partials.classic-site-scripts')
    @endif

    @stack('scripts')
  </body>
</html>

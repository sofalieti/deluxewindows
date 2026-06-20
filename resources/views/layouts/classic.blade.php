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
    <link href="https://cdn.prod.website-files.com" rel="preconnect" crossorigin="anonymous" />
    <title>@yield('title', 'Deluxe Windows | Window Replacement – San Francisco Bay Area')</title>
    <meta content="@yield('metaDescription')" name="description" />
    <meta content="@hasSection('ogTitle')@yield('ogTitle')@else@yield('title')@endif" property="og:title" />
    <meta content="@hasSection('ogDescription')@yield('ogDescription')@else@yield('metaDescription')@endif" property="og:description" />
    @hasSection('ogImage')
    <meta content="@yield('ogImage')" property="og:image" />
    @endif
    <meta content="@hasSection('ogTitle')@yield('ogTitle')@else@yield('title')@endif" name="twitter:title" />
    <meta content="@hasSection('ogDescription')@yield('ogDescription')@else@yield('metaDescription')@endif" name="twitter:description" />
    @hasSection('ogImage')
    <meta content="@yield('ogImage')" name="twitter:image" />
    @endif
    <meta property="og:type" content="@yield('ogType', 'website')" />
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

    @yield('head')

    <link href="https://core.service.elfsight.com/" rel="preconnect" crossorigin="" />
  </head>

  <body class="@yield('bodyClass')">
    <div class="page-wrapper @yield('pageWrapperClass')">
      @include('partials.navbar')

      @yield('content')

      @include('partials.footer')
    </div>

    <div id="menuDimmer" style="opacity: 0; pointer-events: none"></div>

    @hasSection('bodyScripts')
      @yield('bodyScripts')
    @else
      @include('partials.classic-site-scripts')
    @endif

    @stack('scripts')
  </body>
</html>

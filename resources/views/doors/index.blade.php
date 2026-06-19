<!DOCTYPE html>
<html
  data-wf-domain="www.deluxewindows.com"
  data-wf-page="6841ddf8ace3d9d9facb15cd"
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
    <meta content="{{ $ogImage }}" name="twitter:image" />
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
            <div class="breadcrumb-text">Doors</div>
          </div>
        </div>
      </section>

      <section class="section hero-v4">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="w-layout-vflex inner-container _500px---mbl center">
            <div class="mg-top-small">
              <h1 class="display-10 mid text-light">High-Quality Doors <br />for Your Home</h1>
            </div>
          </div>
          <div class="mg-top-small">
            <div class="inner-container _562px center">
              <div class="text-neutral-light">
                <p class="paragraph-26">Upgrade your home with secure, stylish, and energy-efficient doors. Explore a full range of entry and patio doors made for American homes.</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="section">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="collection-list-wrapper-2 w-dyn-list">
            <div role="list" class="grid-2-columns properties-grid---v1 w-dyn-items">
              @foreach($doors as $door)
                @include('partials.doors-index-card', ['door' => $door])
              @endforeach
            </div>
          </div>
        </div>
      </section>

      @include('partials.footer')
    </div>

    <div id="menuDimmer" style="opacity: 0; pointer-events: none"></div>

    @include('partials.classic-site-scripts')
  </body>
</html>

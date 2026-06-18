<!DOCTYPE html>
<html
  data-wf-domain="www.deluxewindows.com"
  data-wf-page="684d99edd99a23e6749ec7b8"
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

    @include('partials.classic-layout-styles')

    <style>
      .w-webflow-badge { display: none !important; }
    </style>

  </head>

  <body>
    <div class="page-wrapper">

      @include('partials.navbar')

      <section class="section-card-wrapper top">
        <div class="section-card hero-card---120px-page">
          <div class="w-layout-blockcontainer container-default w-container">
            <div class="inner-container _850px center">
              <div class="inner-container _600px---tablet center">
                <div class="center-content">
                  <div class="w-layout-vflex inner-container _500px---mbl center">
                    <div class="mg-top-small">
                      <h1 class="display-10 mid text-light">Frequently Asked Questions</h1>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="section pd-120px">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="grid-2-columns template-page-sidebar">
            <div class="card template-pages---sticky-card">
              <ul role="list" class="template-pages---sidebar-navigation w-list-unstyled">
                @foreach($navItems as $item)
                <li class="template-pages---nav-item-wrapper">
                  <a href="#{{ $item['id'] }}" class="template-pages---nav-item-link">{{ $item['label'] }}</a>
                </li>
                @endforeach
              </ul>
            </div>

            <div class="card template-pages---text-card">
              @foreach($sections as $sectionIndex => $section)
              @if($sectionIndex > 0)
              <div class="divider mg-large"></div>
              @endif
              <div id="{{ $section['id'] }}">
                <h2 class="mg-bottom-small">{{ $section['title'] }}</h2>
                @foreach($section['blocks'] as $block)
                @if($block['tag'] === 'h4')
                <h4 class="{{ $block['class'] ?? '' }}">{!! $block['html'] !!}</h4>
                @else
                <{{ $block['tag'] }} class="{{ $block['class'] ?? '' }}">{!! $block['html'] !!}</{{ $block['tag'] }}>
                @endif
                @endforeach
              </div>
              @endforeach
            </div>
          </div>
        </div>
      </section>

      <div class="divider mg-large"></div>

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

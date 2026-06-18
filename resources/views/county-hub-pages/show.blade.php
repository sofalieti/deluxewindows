<!DOCTYPE html>
<html
  data-wf-domain="www.deluxewindows.com"
  data-wf-page="69ce789764cd8d5d1bcf1ae2"
  data-wf-site="6841ddf8ace3d9d9facb14fd"
  lang="en"
  data-wf-collection="69ce789764cd8d5d1bcf1aa4"
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

  <body class="body-20">
    @include('partials.navbar')
    @include('partials.header-scripts')

    @include('partials.county-hub-hero', [
      'countyName' => $countyName,
      'heroImage' => $heroImage,
    ])

    @include('partials.trust-badges')

    <section class="sectionmain">
      <div class="w-layout-blockcontainer container-default w-container">
        <div class="w-layout-grid grid-545 _324234">
          <div class="div-block-63 title-left---content-right---title-grow-v1">
            <div class="code-embed-9 w-embed">
              <h2 class="display-8 mid types">Window Replacement Services in {{ $countyName }}</h2>
            </div>
            @if($countyIntro)
            <div class="rich-text-block-11 w-richtext">
              {!! $countyIntro !!}
            </div>
            @endif
            @if($cities->count() > 0)
            <div class="collection-list-wrapper-22 w-dyn-list">
              <div role="list" class="collection-list-15 w-dyn-items">
                @foreach($cities as $city)
                <div role="listitem" class="collection-item-14 w-dyn-item">
                  <a href="/window-replacement/{{ $city['slug'] }}" class="city-block w-inline-block">
                    <div>{{ $city['name'] }}</div>
                  </a>
                </div>
                @endforeach
              </div>
            </div>
            @endif
          </div>
        </div>
      </div>
    </section>

    @include('partials.county-hub-pricing', ['countyName' => $countyName])
    @include('partials.county-hub-process', ['countyName' => $countyName])
    @include('partials.county-hub-bottom-cta', ['countyName' => $countyName])

    @include('partials.footer')

    <div id="menuDimmer" style="opacity: 0; pointer-events: none"></div>

    @include('partials.classic-site-scripts')
  </body>
</html>

<!DOCTYPE html>
<html
  data-wf-domain="www.deluxewindows.com"
  data-wf-site="6841ddf8ace3d9d9facb14fd"
  lang="en"
>
  <head>
    <meta charset="utf-8" />
    <title>{{ $seoTitle }} | Deluxe Windows</title>
    <meta content="{{ $seoDescription }}" name="description" />
    <meta content="{{ $ogTitle }}" property="og:title" />
    <meta content="{{ $ogDescription }}" property="og:description" />
    @if($ogImage)
    <meta content="{{ $ogImage }}" property="og:image" />
    @endif
    <meta content="{{ $ogTitle }}" name="twitter:title" />
    <meta content="{{ $ogDescription }}" name="twitter:description" />
    @if($ogImage)
    <meta content="{{ $ogImage }}" name="twitter:image" />
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

    <style>
      .w-webflow-badge { display: none !important; }

      /* Brand Collections page */
      .glass-grid-wrapper,
      .options-grid-wrapper,
      .colors-grid-wrapper {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        margin-top: 16px;
      }
      .glass-item, .option-item, .color-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        width: calc(25% - 12px);
        min-width: 120px;
      }
      .glass-img, .option-img, .color-img {
        width: 100%;
        height: 100px;
        object-fit: cover;
        border-radius: 6px;
        margin-bottom: 8px;
      }
      .inspiration-grid-wrapper {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        margin-top: 16px;
      }
      .inspiration-img {
        width: 100%;
        aspect-ratio: 4/3;
        object-fit: cover;
        border-radius: 8px;
      }
      .h3-collection {
        font-size: 1.1rem;
        font-weight: 700;
        margin: 24px 0 8px;
        color: #1E73B9;
      }
      @media (max-width: 768px) {
        .glass-item, .option-item, .color-item { width: calc(50% - 8px); }
        .inspiration-grid-wrapper { grid-template-columns: repeat(2, 1fr); }
      }
      @media (max-width: 480px) {
        .glass-item, .option-item, .color-item { width: 100%; }
        .inspiration-grid-wrapper { grid-template-columns: 1fr; }
      }
    </style>

    <!-- Google tag -->
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
        }
        document.addEventListener("DOMContentLoaded", loadGtag);
        document.addEventListener("mousemove", loadGtag, { once: true });
        document.addEventListener("touchstart", loadGtag, { once: true });
        document.addEventListener("scroll", loadGtag, { once: true });
      })();
    </script>
  </head>

  <body class="body">

    @include('partials.navbar')
    @include('partials.trust-badges')

    {{-- ===== SIDEBAR ===== --}}
    <section class="section_sidebar">
      <div data-delay="0" data-hover="false" class="dropdown-tab tabs-mob sidebar-dropdown w-dropdown">
        <div data-dd="toggle" class="toggle-tab tabs-mob sidebar is-first w-dropdown-toggle" role="button" tabindex="0">
          <div class="toggle-text-tab-2 sidebar-txt">Other collections</div>
          <div class="tab-icon-wrapper sidebar-icon-wrapper">
            <svg xmlns="http://www.w3.org/2000/svg" width="100%" viewBox="0 0 9 6" fill="none" class="sidebar-icon">
              <path d="M4.5 5.55005L0 1.05005L1.05 4.86076e-05L4.5 3.45005L7.95 4.86076e-05L9 1.05005L4.5 5.55005Z" fill="currentColor"></path>
            </svg>
          </div>
        </div>
        <nav data-dd="list" class="dropdown-list-4 sidebar-list w-dropdown-list">
          <div class="sidebar_content-wrapper-2 bottom">
            @if($brandLogo)
            <x-img loading="lazy" :src="$brandLogo" :alt="$brandName" preset="logo" class="svg50 sidebar-svg top-svg" />
            @endif
            <a href="/brand" class="all-brands-block w-inline-block" tabindex="0">
              <div class="icon-font-rounded arrow">о "</div>
              <div class="text-size-14">All brands</div>
            </a>
            @if($sidebarGroups->isNotEmpty())
            <div class="scroll-block">
              @foreach($sidebarGroups as $materialGroup => $groupCollections)
              <div data-delay="0" data-hover="false" class="dropdown-2 w-dropdown">
                <div class="dropdown-toogle-2 dd-toggle sidebar-toggle w-dropdown-toggle" role="button" tabindex="0">
                  <div>{{ $materialGroup ?: 'Other' }}</div>
                  <div class="icon-font-rounded-2 dropdown-arrow sidebar-icon hidden">о ґ</div>
                </div>
                <nav class="dropdown-list-2 dd-sidebar no-borders w-dropdown-list">
                  <div class="w-dyn-list">
                    <div role="list" class="dropdown-list-2 no-padding d-sidebar w-dyn-items">
                      @foreach($groupCollections as $sc)
                      <div role="listitem" class="w-dyn-item">
                        <a href="/brand-collections/{{ $sc['slug'] }}"
                           class="sidebar-item-2 w-inline-block{{ $sc['slug'] === $slug ? ' w--current' : '' }}">
                          @if($sc['image'])
                          <x-img loading="lazy" :src="$sc['image']" :alt="$sc['name']" preset="sidebar" class="sidebar-img" />
                          @endif
                          <div class="sidebar-txt">{{ $sc['name'] }}</div>
                        </a>
                      </div>
                      @endforeach
                    </div>
                  </div>
                </nav>
              </div>
              @endforeach
            </div>
            @endif
          </div>
        </nav>
      </div>
    </section>

    <div class="main-pages-3 relative">
      <div class="section_hero-collection">

        {{-- ===== BREADCRUMBS ===== --}}
        <section class="section_breadcrumbs section-121">
          <div class="w-layout-blockcontainer container-default breadcrumbs-container w-container">
            <div class="breadcrumbs-wrapper">
              <a href="/" class="breadcrumb-link">Home</a>
              <div class="breadcrumb-div">/</div>
              <a href="/brand" class="breadcrumb-link hidden-link">Brands</a>
              @if($brandSlug)
              <div class="breadcrumb-div hidden-txt">/</div>
              <a href="/brands/{{ $brandSlug }}" class="breadcrumb-link hidden-link">{{ $brandName }}</a>
              @endif
              <div class="breadcrumb-div hidden-txt">/</div>
              <div class="breadcrumb-text">{{ $name }}</div>
            </div>
          </div>
        </section>

        {{-- ===== HERO ===== --}}
        <div class="container-default-7 _1100 top0 collection">
          <div class="hero_collection-wrapper">
            @if($brandLogo)
            <x-img data-w-id="22412492-4009-ced3-16dc-8fb2668c31b3"
                 :alt="$brandName"
                 :src="$brandLogo"
                 preset="logo"
                 loading="lazy"
                 class="svg50 hidden-desktop" />
            @endif
            <h1 class="heading-48">{{ $name }}</h1>
            @if($description)
            <p class="collection-paragraph big-p">{{ $description }}</p>
            @endif
            <div class="properties-wrapper">
              @if($material)
              <div class="w-dyn-list">
                <div role="list" class="collection-list-10 w-dyn-items">
                  <div role="listitem" class="collection-item-7 w-dyn-item">
                    <p class="collection-paragraph black-p">{{ $material }}</p>
                    <p class="collection-paragraph black-p"> <span class="text-span">|</span> </p>
                  </div>
                </div>
              </div>
              @endif
              @if($priceCategory)
              <p class="collection-paragraph black-p price-span"> <span class="text-span">|</span> </p>
              <p class="collection-paragraph black-p">{{ $priceCategory }}</p>
              @endif
            </div>
          </div>

          {{-- Form card --}}
          <div class="card-2 sidebar-v1---card new-design hero-section">
            <div class="inner-container _400px---mbl">
              <div class="text-titles-3">
                <div class="display-41 mid">Get Deluxe Windows for Less. 40% OFF* Windows</div>
              </div>
              <div class="mg-top-small-4">
                <p class="text-titles-3"><em>Request a FREE No-Obligation Quote &amp; Expert Advice!</em><br /></p>
              </div>
            </div>
            <div class="mg-top-default-4">
              <div class="sidebar-form-block-v1 sidebar w-form">
                <form id="wf-form-Collection-Form" name="wf-form-Collection-Form" data-name="Collection Form" method="get" class="form-wrapper" aria-label="Collection Form">
                  <div class="grid-1-column-2 gap-row-12">
                    <div class="input-wrapper-5">
                      <div class="input-line-icon-wrapper-4"><div class="filled-icons-font">оў–</div></div>
                      <input class="input-2 icon-left w-input" maxlength="256" name="Name" placeholder="Full name" type="text" id="name-col" required="">
                    </div>
                    <div class="input-wrapper-5">
                      <div class="input-line-icon-wrapper-4"><div class="filled-icons-font">оўЏ</div></div>
                      <input class="input-2 icon-left w-input" maxlength="256" name="Email" placeholder="Email address" type="email" id="email-col" required="">
                    </div>
                    <div class="input-wrapper-5">
                      <div class="input-line-icon-wrapper-4"><div class="filled-icons-font">оЎі</div></div>
                      <input class="input-2 icon-left w-input" maxlength="256" name="Phone" placeholder="Phone number" type="tel" id="phone-col" required="">
                    </div>
                    <div class="input-wrapper-5">
                      <input class="input-2 icon-left w-input" maxlength="256" name="Subject" placeholder="City" type="text" id="subject-col" required="">
                      <div class="input-line-icon-wrapper">
                        <img loading="eager" src="/webflow-assets/images/star-icon-property-x-webflow-template.svg" alt="Star Icon" width="18" height="18" />
                      </div>
                    </div>
                    <div id="w-node-col-submit" class="primary-button-6 space-between-v1">
                      <input type="submit" data-wait="Please wait..." class="inside-input-button-4 text-light w-button" value="Get Your Free Estimate" />
                    </div>
                  </div>
                </form>
                <div class="success-message-wrapper w-form-done" tabindex="-1" role="region" aria-label="Collection Form success">
                  <div class="item-icon-left"><div class="icon-font-rounded-5 success-message-icon">о І</div></div>
                  <div class="mg-top-extra-small-2">
                    <div class="text-titles-3"><div class="display-40">Thank you! We'll get back to you soon<br /></div></div>
                  </div>
                </div>
                <div class="error-message-wrapper-4 w-form-fail" tabindex="-1" role="region" aria-label="Collection Form failure">
                  <div>Oops! Something went wrong.</div>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>{{-- /.section_hero-collection --}}
    </div>{{-- /.main-pages-3 --}}

    {{-- ===== TAB NAVIGATION ===== --}}
    <section class="section_tabs-2">
      <div class="section_tabs-wrapper">
        <a href="#tab1" class="button-tab w-button">About Collection</a>
        @if($windowTypes->isNotEmpty())
        <a href="#tab2" class="button-tab w-button">Windows Types</a>
        @endif
        @if($glassItems->isNotEmpty())
        <a href="#tab3" class="button-tab w-button">Glass</a>
        @endif
        @if($optionItems->isNotEmpty())
        <a href="#tab4-options" class="button-tab w-button">Options</a>
        @endif
        @if($colorItems->isNotEmpty())
        <a href="#tab5" class="button-tab w-button">Colors</a>
        @endif
        @if(!empty($inspirationPhotos))
        <a href="#tab8" class="button-tab w-button">Gallery</a>
        @endif
      </div>
    </section>

    {{-- ===== TAB 1: ABOUT COLLECTION ===== --}}
    <section id="tab1" class="section_about">
      <div class="section_white-2">
        <div class="container-default-7 is-dropdown is-first">
          <div data-delay="0" data-hover="false" class="dropdown-tab tabs-mob is-first w-dropdown">
            <div data-dd="toggle" class="toggle-tab tabs-mob is-first w-dropdown-toggle" role="button" tabindex="0">
              <div class="tab-icon-wrapper">
                <div class="tab-icon-line"></div>
                <div class="second-wrap"><div class="tab-icon-line second"></div></div>
              </div>
              <div class="toggle-text-tab-2">About collection</div>
            </div>
            <nav data-dd="list" class="dropdown-list-4 w-dropdown-list">
              <div class="section_wrapper-2">
                <h2 class="h2-collection">About the collection</h2>
                <div class="about_content-wrapper aligned-tip">
                  <div class="about_left">
                    @if($aboutDescription)
                    <p class="collection-p-big-2">{{ $aboutDescription }}</p>
                    @endif

                    @if(!empty($advantages))
                    <div class="left_grid-wrapper">
                      @foreach($advantages as $adv)
                      <div class="left_grid-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="100%" viewBox="0 0 25 28" fill="none" class="colleciton-icon">
                          <path d="M12.01 23.7222C9.75767 23.155 7.8983 21.8628 6.43186 19.8454C4.96543 17.8281 4.23221 15.5879 4.23221 13.1249V7.19439L12.01 4.27772L19.7878 7.19439V13.1249C19.7878 15.5879 19.0545 17.8281 17.5881 19.8454C16.1217 21.8628 14.2623 23.155 12.01 23.7222ZM12.01 21.6805C13.6952 21.1458 15.0887 20.0763 16.1905 18.4722C17.2924 16.868 17.8433 15.0856 17.8433 13.1249V8.5312L12.01 6.34369L6.17665 8.5312V13.1249C6.17665 15.0856 6.72758 16.868 7.82943 18.4722C8.93128 20.0763 10.3248 21.1458 12.01 21.6805Z" fill="#1E73B9"></path>
                        </svg>
                        <div class="left_grid-content">
                          <h4 class="wtypes-h4-2">{{ $adv['title'] }}</h4>
                          @if($adv['description'])
                          <p class="collection-paragraph">{{ $adv['description'] }}</p>
                          @endif
                        </div>
                      </div>
                      @endforeach
                    </div>
                    @elseif($aboutHtml)
                    <div class="collection-rich-text">{!! $aboutHtml !!}</div>
                    @endif
                  </div>
                  @if($featuredImage)
                  <div class="about-right">
                    <x-img loading="lazy" :src="$featuredImage" :alt="$name" preset="card" class="image-32 right100" />
                  </div>
                  @endif
                </div>
              </div>
            </nav>
          </div>
        </div>
      </div>
    </section>

    {{-- ===== TAB 2: WINDOW TYPES ===== --}}
    @if($windowTypes->isNotEmpty())
    <section id="tab2" class="section_wtypes">
      <div class="section_white-2">
        <div class="container-default-7 is-dropdown">
          <div data-delay="0" data-hover="false" class="dropdown-tab tabs-mob w-dropdown">
            <div data-dd="toggle" class="toggle-tab tabs-mob w-dropdown-toggle" role="button" tabindex="0">
              <div class="tab-icon-wrapper">
                <div class="tab-icon-line"></div>
                <div class="second-wrap"><div class="tab-icon-line second"></div></div>
              </div>
              <div class="toggle-text-tab-2">Window Types</div>
            </div>
            <nav data-dd="list" class="dropdown-list-4 w-dropdown-list">
              <div class="section_wrapper-2">
                <h2 class="h2-collection">Window Types</h2>
                <div class="collection-list-wrapper-15 w-dyn-list">
                  <div role="list" class="wtypes_grid-wrapper-2 w-dyn-items">
                    @foreach($windowTypes as $wt)
                    <div role="listitem" class="collection-item-5 w-dyn-item">
                      <div class="wtypes_grid-item-2 animated-scroll">
                        @if($wt['picture'])
                        <x-img loading="lazy" :src="$wt['picture']" :alt="$wt['name']" preset="wtype" class="wtypes-img" />
                        @endif
                        <h4 class="wtypes-h4-3">{{ $wt['name'] }}</h4>
                        @if($wt['description'])
                        <p class="collection-paragraph hidden-p">{{ $wt['description'] }}</p>
                        @endif
                      </div>
                    </div>
                    @endforeach
                  </div>
                </div>
              </div>
            </nav>
          </div>
        </div>
      </div>
    </section>
    @endif

    {{-- ===== TAB 3: GLASS ===== --}}
    @if($glassItems->isNotEmpty())
    @php $glassGroups = $glassItems->groupBy('subcategory'); @endphp
    <section id="tab3" class="section_glass">
      <div class="section_white-2">
        <div class="container-default-7 is-dropdown">
          <div data-delay="0" data-hover="false" class="dropdown-tab tabs-mob w-dropdown">
            <div data-dd="toggle" class="toggle-tab tabs-mob w-dropdown-toggle" role="button" tabindex="0">
              <div class="tab-icon-wrapper">
                <div class="tab-icon-line"></div>
                <div class="second-wrap"><div class="tab-icon-line second"></div></div>
              </div>
              <div class="toggle-text-tab-2">Glass Packages</div>
            </div>
            <nav data-dd="list" class="dropdown-list-4 w-dropdown-list">
              <div class="section_wrapper-2">
                <h2 class="h2-collection">Glass Packages</h2>
                @foreach($glassGroups as $subcategory => $items)
                @if($subcategory)
                <h3 class="h3-collection">{{ $subcategory }}</h3>
                @endif
                <div class="glass-grid-wrapper">
                  @foreach($items as $glass)
                  <div class="glass-item animated-scroll">
                    @if($glass['picture'])
                    <x-img loading="lazy" :src="$glass['picture']" :alt="$glass['name']" preset="glass" class="glass-img" />
                    @endif
                    <p class="collection-paragraph">{{ $glass['name'] }}</p>
                    @if($glass['description'])
                    <p class="collection-paragraph small-p">{{ $glass['description'] }}</p>
                    @endif
                  </div>
                  @endforeach
                </div>
                @endforeach
              </div>
            </nav>
          </div>
        </div>
      </div>
    </section>
    @endif

    {{-- ===== TAB 4 OPTIONS ===== --}}
    @if($optionItems->isNotEmpty())
    @php $optionGroups = $optionItems->groupBy('subcategory'); @endphp
    <section id="tab4-options" class="section_options">
      <div class="section_white-2">
        <div class="container-default-7 is-dropdown">
          <div data-delay="0" data-hover="false" class="dropdown-tab tabs-mob w-dropdown">
            <div data-dd="toggle" class="toggle-tab tabs-mob w-dropdown-toggle" role="button" tabindex="0">
              <div class="tab-icon-wrapper">
                <div class="tab-icon-line"></div>
                <div class="second-wrap"><div class="tab-icon-line second"></div></div>
              </div>
              <div class="toggle-text-tab-2">Options &amp; Accessories</div>
            </div>
            <nav data-dd="list" class="dropdown-list-4 w-dropdown-list">
              <div class="section_wrapper-2">
                <h2 class="h2-collection">Options &amp; Accessories</h2>
                @foreach($optionGroups as $subcategory => $items)
                @if($subcategory)
                <h3 class="h3-collection">{{ $subcategory }}</h3>
                @endif
                <div class="options-grid-wrapper">
                  @foreach($items as $option)
                  <div class="option-item animated-scroll">
                    @if($option['picture'])
                    <x-img loading="lazy" :src="$option['picture']" :alt="$option['name']" preset="option" class="option-img" />
                    @endif
                    <p class="collection-paragraph">{{ $option['name'] }}</p>
                    @if($option['description'])
                    <p class="collection-paragraph small-p">{{ $option['description'] }}</p>
                    @endif
                  </div>
                  @endforeach
                </div>
                @endforeach
              </div>
            </nav>
          </div>
        </div>
      </div>
    </section>
    @endif

    {{-- ===== TAB 5: COLORS ===== --}}
    @if($colorItems->isNotEmpty())
    @php $colorGroups = $colorItems->groupBy('subcategory'); @endphp
    <section id="tab5" class="section_colors">
      <div class="section_white-2">
        <div class="container-default-7 is-dropdown">
          <div data-delay="0" data-hover="false" class="dropdown-tab tabs-mob w-dropdown">
            <div data-dd="toggle" class="toggle-tab tabs-mob w-dropdown-toggle" role="button" tabindex="0">
              <div class="tab-icon-wrapper">
                <div class="tab-icon-line"></div>
                <div class="second-wrap"><div class="tab-icon-line second"></div></div>
              </div>
              <div class="toggle-text-tab-2">Colors</div>
            </div>
            <nav data-dd="list" class="dropdown-list-4 w-dropdown-list">
              <div class="section_wrapper-2">
                <h2 class="h2-collection">Colors</h2>
                @foreach($colorGroups as $subcategory => $items)
                @if($subcategory)
                <h3 class="h3-collection">{{ $subcategory }}</h3>
                @endif
                <div class="colors-grid-wrapper">
                  @foreach($items as $color)
                  <div class="color-item animated-scroll">
                    @if($color['picture'])
                    <x-img loading="lazy" :src="$color['picture']" :alt="$color['name']" preset="color" class="color-img" />
                    @elseif($color['color'])
                    <div class="color-swatch" style="background-color: {{ $color['color'] }}; width:60px; height:60px; border-radius:4px; border:1px solid #ddd;"></div>
                    @endif
                    <p class="collection-paragraph">{{ $color['name'] }}</p>
                  </div>
                  @endforeach
                </div>
                @endforeach
              </div>
            </nav>
          </div>
        </div>
      </div>
    </section>
    @endif

    {{-- ===== TAB 8: INSPIRATION PHOTOS ===== --}}
    <section id="tab8" class="section_iphotos">
      <div class="section_white-2">
        <div class="container-default-7 is-dropdown">
          <div data-delay="0" data-hover="false" class="dropdown-tab tabs-mob w-dropdown">
            <div data-dd="toggle" class="toggle-tab tabs-mob w-dropdown-toggle" role="button" tabindex="0">
              <div class="tab-icon-wrapper">
                <div class="tab-icon-line"></div>
                <div class="second-wrap"><div class="tab-icon-line second"></div></div>
              </div>
              <div class="toggle-text-tab-2">Inspiration Photos</div>
            </div>
            <nav data-dd="list" class="dropdown-list-4 w-dropdown-list">
              <div class="section_wrapper-2">
                <h2 class="h2-collection">Inspiration Photos</h2>
                @if(!empty($inspirationPhotos))
                <div class="inspiration-grid-wrapper">
                  @foreach($inspirationPhotos as $photo)
                  <div class="inspiration-item animated-scroll">
                    <x-img loading="lazy" :src="$photo" :alt="$name . ' inspiration'" preset="inspiration" class="inspiration-img" />
                  </div>
                  @endforeach
                </div>
                @else
                <div class="w-dyn-empty"><div>No items found.</div></div>
                @endif
              </div>
            </nav>
          </div>
        </div>
      </div>
    </section>

    {{-- ===== CTA ===== --}}
    <section id="tab4" class="section_cta-small">
      <div class="section_white-2 transparent">
        <div class="container-default-7">
          <div class="section_wrapper-2 center-align transparent">
            <h2 class="h2-collection align-center non-hidden">Ready to Upgrade Your Windows?</h2>
            <p class="collection-paragraph align-center">
              Get a free, no-obligation quote for the <span>{{ $name }}</span> and see how these windows can transform your home.
            </p>
            <a href="/contacts" class="primary-button-6 sidebar-button w-inline-block">
              <div class="text-block">Request a Quote</div>
            </a>
          </div>
        </div>
      </div>
    </section>

    <div class="section-120">
      <div class="w-layout-blockcontainer container-default-7 w-container">
        <p class="collection-paragraph small-p align-center">
          * Price applies to minimum window installation size of 24"x24".
        </p>
      </div>
    </div>

    @include('partials.navbar')

    {{-- Scripts --}}
    <script src="/webflow-assets/js/jquery-3.5.1.min.dc5e7f18c8.js" type="text/javascript"></script>
    <script src="/webflow-assets/js/webflow-brand-collections.js" type="text/javascript"></script>

    {{-- Tab scroll behaviour --}}
    <script>
    (function () {
      var tabs = document.querySelectorAll('.button-tab');
      tabs.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
          e.preventDefault();
          var target = btn.getAttribute('href');
          if (!target || target === '#') return;
          var section = document.querySelector(target);
          if (section) {
            section.scrollIntoView({ behavior: 'smooth', block: 'start' });
          }
          tabs.forEach(function (b) { b.classList.remove('w--current'); });
          btn.classList.add('w--current');
        });
      });
    })();
    </script>

  </body>
</html>

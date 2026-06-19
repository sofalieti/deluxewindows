<!DOCTYPE html>
<html
  data-wf-domain="www.deluxewindows.com"
  data-wf-page="69366119c296b5e2e8bdbfb8"
  data-wf-site="6841ddf8ace3d9d9facb14fd"
  data-wf-collection="{{ $webflowCollectionId }}"
  data-wf-item-slug="{{ $slug }}"
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
        var n = c.documentElement, t = " w-mod-";
        n.className += t + "js";
        ("ontouchstart" in o || (o.DocumentTouch && c instanceof DocumentTouch)) && (n.className += t + "touch");
      })(window, document);
    </script>
    <link href="/webflow-assets/images/favicon.png" rel="shortcut icon" type="image/x-icon" />
    <link href="/webflow-assets/images/webclip-bg.png" rel="apple-touch-icon" />

    @include('partials.classic-layout-styles')


  </head>

  <body>
    <div class="page-wrapper">
      @include('partials.navbar')
      @include('partials.trust-badges')

      <div class="container-default">
        <div class="parent_sticky-wrapper sidebar-left">
          <section class="section_sidebar">
            @include('partials.brands-sidebar', [
              'name' => $brandName,
              'logo' => $brandLogo,
              'sidebarMaterialGroups' => $sidebarMaterialGroups,
              'windowTypes' => collect(),
              'sidebarLabel' => 'Other collections',
              'currentCollectionSlug' => $slug,
              'allBrandsHref' => '/brands',
              'hideSidebarInlineForm' => true,
              'wfPageId' => '69366119c296b5e2e8bdbfb8',
            ])

          </section>

          <div class="main-pages-3 relative">
            <div class="section_hero-collection">
              <section class="section_breadcrumbs section-121">
                <div class="w-layout-blockcontainer container-default breadcrumbs-container w-container">
                  <div class="breadcrumbs-wrapper">
                    <a href="/" class="breadcrumb-link">Home</a>
                    <div class="breadcrumb-div">/</div>
                    <a href="/brands" class="breadcrumb-link hidden-link">Brands</a>
                    @if($brandSlug)
                    <div class="breadcrumb-div hidden-txt">/</div>
                    <a href="/brands/{{ $brandSlug }}" class="breadcrumb-link hidden-link">{{ $brandName }}</a>
                    @endif
                    <div class="breadcrumb-div hidden-txt">/</div>
                    <div class="breadcrumb-text">{{ $name }}</div>
                  </div>
                </div>
              </section>

              <div class="container-default-7 _1100 top0 collection">
                <div class="hero_collection-wrapper">
                  @if($brandLogoSvg ?? $brandLogo)
                  <img loading="lazy" src="{{ $brandLogoSvg ?? $brandLogo }}" alt="{{ $brandName }}" class="svg50 hidden-desktop" />
                  @endif
                  <h1 class="heading-48">{{ $name }}</h1>
                  @if($longDescription)
                  <p class="collection-paragraph big-p">{{ $longDescription }}</p>
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

              </div>
            </div>

            <section class="section_tabs-2">
              <div class="section_tabs-wrapper">
                <a href="#tab1" class="button-tab w-button">About Collection</a>
                @if($windowTypes->isNotEmpty())
                <a href="#tab2" class="button-tab w-button">Windows Types</a>
                @endif
                @if($hasGlassTab)
                <a href="#tab3" class="button-tab w-button">Glass</a>
                @endif
                @if($hasOptionsTab)
                <a href="#tab4" class="button-tab w-button">Options</a>
                @endif
                @if($exteriorColors->isNotEmpty() || $interiorColors->isNotEmpty())
                <a href="#tab5" class="button-tab w-button">Colors</a>
                @endif
                @if(!empty($inspirationPhotos))
                <a href="#tab8" class="button-tab w-button">Gallery</a>
                @endif
              </div>
            </section>

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
                                @include('partials.collection-advantage-icon', ['index' => $loop->index])
                                <div class="left_grid-content">
                                  <h4 class="wtypes-h4-2">{{ $adv['title'] }}</h4>
                                  @if($adv['description'])
                                  <p class="collection-paragraph">{!! nl2br(e($adv['description'])) !!}</p>
                                  @endif
                                </div>
                              </div>
                              @endforeach
                            </div>
                            @if($configurationSizes && $configurationSizesDescription !== '')
                            <h4 class="wtypes-h4 is-features">Size &amp; Strength</h4>
                            <p class="collection-paragraph">{!! nl2br(e($configurationSizesDescription)) !!}</p>
                            @endif
                            @if($performance && $performanceDescription !== '')
                            <h4 class="wtypes-h4 is-features">Performance</h4>
                            <p class="collection-paragraph">{!! nl2br(e($performanceDescription)) !!}</p>
                            @endif
                            @elseif($aboutHtml)
                            <div class="collection-rich-text w-richtext">{!! $aboutHtml !!}</div>
                            @endif
                          </div>
                          @if($aboutImage)
                          <div class="about-right">
                            <x-img loading="lazy" :src="$aboutImage" :alt="$name" preset="card" class="image-32 right100" />
                          </div>
                          @endif
                        </div>
                      </div>
                    </nav>
                  </div>
                </div>
              </div>
            </section>

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
                                <p class="collection-paragraph align-center no-hidden">{!! nl2br(e($wt['description'])) !!}</p>
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

            @if($hasGlassTab)
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

                        @if($standardGlass->isNotEmpty())
                        <div class="w-dyn-list">
                          <div role="list" class="wtypes_grid-wrapper-2 _1itemgrid stabdard vertical w-dyn-items">
                            @foreach($standardGlass as $glass)
                            <div role="listitem" class="anlin_vertical w-dyn-item">
                              <div class="wtypes_grid-item-2 glass-item no-cursor standard vertical">
                                <div class="wtypes_inner">
                                  @if($glass['picture'])
                                  <x-img loading="lazy" :src="$glass['picture']" :alt="$glass['name']" preset="glass" class="glass-img-3" />
                                  @endif
                                  <p class="glass-paragraph-2">{{ $glass['name'] }}</p>
                                </div>
                              </div>
                            </div>
                            @endforeach
                          </div>
                        </div>
                        @endif

                        @if($tintedGlass->isNotEmpty())
                        <div class="div-block-57">
                          <h3 class="h3-collection-2">Tinted Glass Options</h3>
                          <div class="collection-list-wrapper-14 w-dyn-list">
                            <div role="list" class="wtypes_grid-wrapper-2 _1itemgrid w-dyn-items">
                              @foreach($tintedGlass as $glass)
                              <div role="listitem" class="w-dyn-item">
                                <div class="wtypes_grid-item-2 glass-item no-cursor">
                                  @if($glass['picture'])
                                  <x-img loading="lazy" :src="$glass['picture']" :alt="$glass['name']" preset="glass" class="glass-img-3" />
                                  @endif
                                  <p class="glass-paragraph-2">{{ $glass['name'] }}</p>
                                </div>
                              </div>
                              @endforeach
                            </div>
                          </div>
                        </div>
                        @endif

                        @if($obscureGlass->isNotEmpty())
                        <div class="div-block-56">
                          <h3 class="h3-collection-2">Obscure Glass Options</h3>
                          <div class="collection-list-wrapper-12 w-dyn-list">
                            <div role="list" class="wtypes_grid-wrapper-2 _1itemgrid w-dyn-items">
                              @foreach($obscureGlass as $glass)
                              <div role="listitem" class="w-dyn-item">
                                <div class="wtypes_grid-item-2 glass-item no-cursor">
                                  @if($glass['picture'])
                                  <x-img loading="lazy" :src="$glass['picture']" :alt="$glass['name']" preset="glass" class="glass-img-2" />
                                  @endif
                                  <p class="glass-paragraph-2">{{ $glass['name'] }}</p>
                                </div>
                              </div>
                              @endforeach
                            </div>
                          </div>
                        </div>
                        @endif
                      </div>
                    </nav>
                  </div>
                </div>
              </div>
            </section>
            @endif

            @if($hasOptionsTab)
            <div id="tab4" class="section_options">
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

                        @if($gridStyles->isNotEmpty())
                        <h3 class="h3-collection-2">Grid Styles</h3>
                        <div class="collection-list-wrapper-11 w-dyn-list">
                          <div role="list" class="wtypes_grid-wrapper-2 grid-styles-wrapper w-dyn-items">
                            @foreach($gridStyles as $option)
                            <div role="listitem" class="w-dyn-item">
                              <a href="#" class="wtypes_grid-item-2 grid-styles w-inline-block w-lightbox">
                                @if($option['picture'])
                                <x-img loading="lazy" :src="$option['picture']" :alt="$option['name']" preset="option" class="wtypes-img" />
                                @endif
                                <h4 class="wtypes-h4-3 align-center">{{ $option['name'] }}</h4>
                                @if($option['description'])
                                <p class="collection-paragraph align-center">{!! nl2br(e($option['description'])) !!}</p>
                                @endif
                                <script type="application/json" class="w-json">{!! json_encode(['items' => $option['picture'] ? [['url' => $option['picture'], 'type' => 'image']] : [], 'group' => '']) !!}</script>
                              </a>
                            </div>
                            @endforeach
                          </div>
                        </div>
                        @endif

                        @if($gridPatternImages->isNotEmpty())
                        <h3 class="h3-collection-2">Grid Patterns</h3>
                        <div class="collection-list-wrapper-11 w-dyn-list">
                          <div role="list" class="wtypes_grid-wrapper-2 grid-styles-wrapper w-dyn-items">
                            @foreach($gridPatternImages as $option)
                            <div role="listitem" class="w-dyn-item">
                              <a href="#" class="wtypes_grid-item-2 grid-styles w-inline-block w-lightbox">
                                <x-img loading="lazy" :src="$option['picture']" :alt="$option['name']" preset="option" class="wtypes-img auto-width" />
                                <h4 class="wtypes-h4-3 align-center">{{ $option['name'] }}</h4>
                                @if($option['description'])
                                <p class="collection-paragraph align-center">{!! nl2br(e($option['description'])) !!}</p>
                                @endif
                                <script type="application/json" class="w-json">{!! json_encode(['items' => [['url' => $option['picture'], 'type' => 'image']], 'group' => '']) !!}</script>
                              </a>
                            </div>
                            @endforeach
                          </div>
                        </div>
                        @endif

                        @if($gridPatternSwatches->isNotEmpty())
                        <h3 class="h3-collection-2">Finishes</h3>
                        <div class="collection-list-wrapper-11 w-dyn-list">
                          <div role="list" class="wtypes_grid-wrapper-2 grid-patterns w-dyn-items">
                            @foreach($gridPatternSwatches as $option)
                            <div role="listitem" class="w-dyn-item">
                              <div class="colors-item">
                                <div class="color-circle" @if($option['color']) style="background-color:{{ $option['color'] }}" @endif></div>
                                <p class="collection-paragraph align-center">{{ $option['name'] }}</p>
                              </div>
                            </div>
                            @endforeach
                          </div>
                        </div>
                        @endif

                        @if($hardwareItems->isNotEmpty())
                        <h3 class="h3-collection-2">Hardware</h3>
                        <div class="w-dyn-list">
                          <div role="list" class="wtypes_grid-wrapper-2 grid-styles-wrapper ext w-dyn-items">
                            @foreach($hardwareItems as $option)
                            <div role="listitem" class="collection-item-5 w-dyn-item">
                              <div class="wtypes_grid-item-2 hardware">
                                @if($option['picture'])
                                <x-img loading="lazy" :src="$option['picture']" :alt="$option['name']" preset="option" class="wtypes-img" />
                                @endif
                                <h4 class="wtypes-h4-3 align-center">{{ $option['name'] }}</h4>
                                @if($option['description'])
                                <p class="collection-paragraph align-center no-hidden">{!! nl2br(e($option['description'])) !!}</p>
                                @endif
                              </div>
                            </div>
                            @endforeach
                          </div>
                        </div>
                        @endif
                      </div>
                    </nav>
                  </div>
                </div>
              </div>
            </div>
            @endif

            @if($exteriorColors->isNotEmpty() || $interiorColors->isNotEmpty())
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
                        <div class="section_colors-content">
                          @if($exteriorColors->isNotEmpty())
                          <div class="section_colors-block">
                            <h3 class="h3-collection-2">Exterior Frame Colors</h3>
                            <div class="collection-list-wrapper-17 w-dyn-list">
                              <div role="list" class="colors_block-wrapper w-dyn-items">
                                @foreach($exteriorColors as $color)
                                <div role="listitem" class="collection-item-9 w-dyn-item">
                                  <div class="non-linked no-cursor">
                                    <div class="colors-item">
                                      <div class="color-circle" @if($color['color']) style="background-color:{{ $color['color'] }}" @endif>
                                        @if($color['picture'])
                                        <x-img :src="$color['picture']" preset="color" loading="lazy" alt="" class="color-img" />
                                        @endif
                                      </div>
                                      <p class="collection-paragraph align-center">{{ $color['name'] }}</p>
                                    </div>
                                  </div>
                                </div>
                                @endforeach
                              </div>
                            </div>
                          </div>
                          @endif
                          @if($interiorColors->isNotEmpty())
                          <div class="section_colors-block">
                            <h3 class="h3-collection-2">Interior Frame Colors</h3>
                            <div class="collection-list-wrapper-16 w-dyn-list">
                              <div role="list" class="colors_block-wrapper w-dyn-items">
                                @foreach($interiorColors as $color)
                                <div role="listitem" class="collection-item-10 w-dyn-item">
                                  <div class="non-linked no-cursor">
                                    <div class="colors-item">
                                      <div class="color-circle" @if($color['color']) style="background-color:{{ $color['color'] }}" @endif>
                                        @if($color['picture'])
                                        <x-img :src="$color['picture']" preset="color" loading="lazy" alt="" class="color-img" />
                                        @endif
                                      </div>
                                      <p class="collection-paragraph align-center">{{ $color['name'] }}</p>
                                    </div>
                                  </div>
                                </div>
                                @endforeach
                              </div>
                            </div>
                          </div>
                          @endif
                        </div>
                      </div>
                    </nav>
                  </div>
                </div>
              </div>
            </section>
            @endif

            <section id="tab8" class="section_iphotos">
              <div class="section_white-2 is-last">
                <div class="container-default-7 is-dropdown last">
                  <div data-delay="0" data-hover="false" class="dropdown-tab tabs-mob is-last w-dropdown">
                    <div data-dd="toggle" class="toggle-tab tabs-mob w-dropdown-toggle" role="button" tabindex="0">
                      <div class="tab-icon-wrapper">
                        <div class="tab-icon-line"></div>
                        <div class="second-wrap"><div class="tab-icon-line second"></div></div>
                      </div>
                      <div class="toggle-text-tab-2">Inspiration Photos</div>
                    </div>
                    <nav data-dd="list" class="dropdown-list-4 w-dropdown-list">
                      <div class="section_wrapper">
                        <h2 class="h2-collection">Inspiration Photos</h2>
                        @if(!empty($inspirationPhotos))
                        <div class="w-dyn-list">
                          <div role="list" class="inspiration_grid-wrapper load-more-list w-dyn-items">
                            @foreach($inspirationPhotos as $photo)
                            <div role="listitem" class="load-more-item w-dyn-item w-dyn-repeater-item">
                              <a href="#" class="inspiration_grid-item is-3 w-inline-block w-lightbox">
                                <x-img loading="lazy" :src="$photo" :alt="$name . ' inspiration'" preset="inspiration" class="image-32" />
                                <script type="application/json" class="w-json">{!! json_encode(['items' => [['url' => $photo, 'type' => 'image']], 'group' => '']) !!}</script>
                              </a>
                            </div>
                            @endforeach
                          </div>
                        </div>
                        <div class="loadmore-wrapper">
                          <a href="#" class="primary-button-6 small loadmore load-more-btn w-inline-block">
                            <div class="text-block">Show more</div>
                          </a>
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
          </div>
        </div>
      </div>

      <div class="section-120"></div>

      <div class="f-section-large-3">
        <div class="f-container-regular-3">
          <div class="f-margin-bottom-64">
            <div class="w-layout-grid f-header-grid-asymmetrical">
              <div class="f-max-width-large w-clearfix">
                <img src="/webflow-assets/images/69986ae97432d22237832ac2_guarantee-icon.svg" loading="lazy" alt="" class="warranty" />
                <h3 class="f-h3-heading-2">Our Guarantees</h3>
              </div>
            </div>
          </div>
          <div class="w-layout-grid f-grid-three-column-2 newww">
            <div class="f-feature-card-filled">
              <div class="f-margin-bottom-129">
                <h5 class="f-h5-heading">{{ $material ?: 'Windows' }}</h5>
                <div class="rich-text-block-9 w-richtext">
                  <p>Offer <strong>20 year</strong> on glass.</p>
                </div>
              </div>
            </div>
            <div class="f-feature-card-filled">
              <div class="f-margin-bottom-129">
                <h5 class="f-h5-heading">Manufacturer's warranty on glass and frame</h5>
              </div>
              <p class="f-paragraph-large-2"><br /><strong>Lifetime</strong></p>
            </div>
            <div class="f-feature-card-filled">
              <div class="f-margin-bottom-129">
                <h5 class="f-h5-heading">All Other Parts<br />‍</h5>
              </div>
              <p class="f-paragraph-large-2"><strong><br />10 Years</strong> Warranty</p>
            </div>
          </div>
        </div>
      </div>

      <section class="section_cta-small">
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
        <section class="new-section">
          <div class="w-layout-blockcontainer container-default w-container">
            <div class="text-block-44">* Price applies to minimum window installation size of 24&quot;x24&quot;.</div>
          </div>
        </section>
      </div>

      @include('partials.footer')
    </div>

    <div id="menuDimmer" style="opacity: 0; pointer-events: none"></div>

    <script src="/webflow-assets/js/jquery-3.5.1.min.js" type="text/javascript"></script>
    <script src="/webflow-assets/js/webflow-brand-collections.js" type="text/javascript"></script>

    <script>
      (function () {
        const TRACK_PARAMS = ["utm_source","utm_medium","utm_campaign","utm_term","utm_content","matchtype","device","creative","gclid"];
        const params = new URLSearchParams(window.location.search);
        const hasUtm = TRACK_PARAMS.some(p => params.get(p));
        if (hasUtm) {
          TRACK_PARAMS.forEach(param => {
            const value = params.get(param);
            if (value) localStorage.setItem("lead_param_" + param, value);
            else localStorage.removeItem("lead_param_" + param);
          });
        } else if (!TRACK_PARAMS.some(p => localStorage.getItem("lead_param_" + p))) {
          localStorage.setItem("lead_param_utm_source", "(direct)");
          localStorage.setItem("lead_param_utm_medium", "(none)");
        }
        if (!localStorage.getItem("lead_param_landing_page")) {
          localStorage.setItem("lead_param_landing_page", window.location.pathname);
        }
        function injectHiddenFields(form) {
          [...TRACK_PARAMS, "landing_page"].forEach(param => {
            if (!form.querySelector('input[name="' + param + '"]')) {
              const input = document.createElement("input");
              input.type = "hidden";
              input.name = param;
              input.value = localStorage.getItem("lead_param_" + param) || "";
              form.appendChild(input);
            }
          });
        }
        document.addEventListener("DOMContentLoaded", function () {
          document.querySelectorAll("form").forEach(injectHiddenFields);
        });
        let lazyLoaded = false;
        function initLazy() {
          if (lazyLoaded) return;
          lazyLoaded = true;
          initForms();
        }
        window.addEventListener("scroll", initLazy, { once: true });
        window.addEventListener("click", initLazy, { once: true });
        setTimeout(initLazy, 4000);
        function initForms() {
          let ipData = {};
          function waitIP(timeout) {
            return Promise.race([
              fetch("https://ipapi.co/json/").then(r => r.json()).then(data => { ipData = data; }).catch(() => {}),
              new Promise(res => setTimeout(res, timeout || 800))
            ]);
          }
          document.querySelectorAll("form").forEach(form => {
            form.addEventListener("submit", function () {
              waitIP().then(() => {
                const formData = new FormData(form);
                formData.append("ip_address", ipData.ip || "");
                formData.append("geo_location", ipData.city || "");
                const body = new URLSearchParams(formData);
                fetch("https://script.google.com/macros/s/AKfycbyJGhNROpBI8TUkGn9RtdNtIDxNjxsI52kyHgBtDIUauSEWgzVIqCFPic0-chwjxNxU/exec", { method: "POST", body, keepalive: true });
                fetch("https://script.google.com/macros/s/AKfycbwp7eg4fm8OZtiHLjAFrbNyPaSyDjZWmfTJyhkiAZ2UsWYmE6l7euH9K0RtdgODH44Rmg/exec", { method: "POST", body, keepalive: true });
              });
            });
          });
        }
      })();
    </script>

    <script>
      (function () {
        if (window.innerWidth > 992) return;
        const toggles = document.querySelectorAll('[data-dd="toggle"]');
        const lists = [];
        function applyScrollBlock(list) {
          const sb = list.querySelector(".scroll-block");
          if (!sb) return;
          const sbRect = sb.getBoundingClientRect();
          const pad = 20;
          const topPos = sbRect.top > 0 ? sbRect.top : 150;
          sb.style.maxHeight = Math.max(120, window.innerHeight - topPos - pad) + "px";
        }
        toggles.forEach(toggle => {
          const list = toggle.parentElement.querySelector('[data-dd="list"]');
          if (!list) return;
          const icon = toggle.querySelector(".tab-icon-line.second");
          const sidebarIcon = toggle.querySelector(".sidebar-icon");
          if (!lists.includes(list)) {
            lists.push(list);
            list.style.overflow = "hidden";
            list.style.maxHeight = "0px";
            list.style.transition = "max-height 0.35s ease";
            list.dataset.open = "false";
            list.style.display = "none";
          }
          toggle.addEventListener("click", () => {
            const isOpen = list.dataset.open === "true";
            lists.forEach(other => {
              if (other !== list && other.dataset.open === "true") {
                const otherToggle = other.parentElement.querySelector('[data-dd="toggle"]');
                const otherIcon = otherToggle?.querySelector(".tab-icon-line.second");
                const otherSidebarIcon = otherToggle?.querySelector(".sidebar-icon");
                other.style.overflow = "hidden";
                other.style.display = "block";
                other.style.maxHeight = other.scrollHeight + "px";
                requestAnimationFrame(() => { other.style.maxHeight = "0px"; });
                other.dataset.open = "false";
                setTimeout(() => { if (other.dataset.open === "false") other.style.display = "none"; }, 360);
                if (otherIcon) otherIcon.style.transform = "rotate(0deg)";
                if (otherSidebarIcon) otherSidebarIcon.style.transform = "rotate(0deg)";
              }
            });
            if (isOpen) {
              list.style.overflow = "hidden";
              list.style.display = "block";
              list.style.maxHeight = list.scrollHeight + "px";
              requestAnimationFrame(() => { list.style.maxHeight = "0px"; });
              list.dataset.open = "false";
              setTimeout(() => { if (list.dataset.open === "false") list.style.display = "none"; }, 360);
              if (icon) icon.style.transform = "rotate(0deg)";
              if (sidebarIcon) sidebarIcon.style.transform = "rotate(0deg)";
            } else {
              list.style.display = "block";
              list.style.overflow = "hidden";
              list.style.maxHeight = "0px";
              requestAnimationFrame(() => { list.style.maxHeight = list.scrollHeight + "px"; });
              list.dataset.open = "true";
              if (icon) { icon.style.transition = "transform 0.35s ease"; icon.style.transform = "rotate(90deg)"; }
              if (sidebarIcon) { sidebarIcon.style.transition = "transform 0.35s ease"; sidebarIcon.style.transform = "rotate(180deg)"; }
              setTimeout(() => {
                if (list.dataset.open === "true") {
                  list.style.maxHeight = "none";
                  list.style.overflow = "visible";
                  applyScrollBlock(list);
                }
              }, 360);
            }
          });
        });
        window.addEventListener("resize", () => {
          lists.forEach(l => { if (l.dataset.open === "true") requestAnimationFrame(() => applyScrollBlock(l)); });
        }, { passive: true });
      })();
    </script>

    <script>
      document.addEventListener("DOMContentLoaded", function () {
        if (window.innerWidth < 992) return;
        const buttons = Array.from(document.querySelectorAll(".button-tab")).filter(btn => btn.offsetParent !== null);
        const sections = buttons
          .map(btn => document.querySelector(btn.getAttribute("href")))
          .filter(sec => sec);
        buttons.forEach((btn) => {
          btn.addEventListener("click", (e) => {
            e.preventDefault();
            const target = document.querySelector(btn.getAttribute("href"));
            if (!target) return;
            const top = target.getBoundingClientRect().top + window.scrollY - 120;
            window.scrollTo({ top, behavior: "smooth" });
            buttons.forEach(b => b.classList.remove("is-active"));
            btn.classList.add("is-active");
          });
        });
        const observer = new IntersectionObserver((entries) => {
          entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            const href = "#" + entry.target.id;
            const activeBtn = buttons.find(b => b.getAttribute("href") === href);
            if (activeBtn) {
              buttons.forEach(b => b.classList.remove("is-active"));
              activeBtn.classList.add("is-active");
            }
          });
        }, { threshold: 0.25 });
        sections.forEach(sec => observer.observe(sec));
      });
    </script>

    <script>
      document.addEventListener("DOMContentLoaded", function () {
        const STEP = 6;
        document.querySelectorAll(".section_wrapper .inspiration_grid-wrapper.load-more-list").forEach(list => {
          const items = Array.from(list.querySelectorAll(".load-more-item"));
          const sectionWrapper = list.closest(".section_wrapper");
          const btn = sectionWrapper ? sectionWrapper.querySelector(".load-more-btn") : null;
          if (!btn || items.length === 0) return;
          let visibleCount = STEP;
          items.forEach((item, index) => { item.style.display = index < STEP ? "" : "none"; });
          if (items.length <= STEP) { btn.style.display = "none"; return; }
          btn.addEventListener("click", function (e) {
            e.preventDefault();
            const nextVisible = visibleCount + STEP;
            for (let i = visibleCount; i < nextVisible && i < items.length; i++) items[i].style.display = "";
            visibleCount = nextVisible;
            if (visibleCount >= items.length) btn.style.display = "none";
          });
        });
      });
    </script>

    <script>
      document.addEventListener("DOMContentLoaded", () => {
        document.querySelectorAll(".collection-paragraph").forEach(el => {
          el.innerHTML = el.innerHTML.replace(/&lt;br\s*\/?&gt;/gi, "<br>");
        });
      });
    </script>
  </body>
</html>

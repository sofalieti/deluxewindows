      @once
        @php
          $siteCustomCssPath = public_path('webflow-overrides/site-custom.css');
          $siteCustomCssVersion = file_exists($siteCustomCssPath) ? (string) filemtime($siteCustomCssPath) : '1';
        @endphp
        <link href="/webflow-overrides/site-custom.css?v={{ $siteCustomCssVersion }}" rel="stylesheet" type="text/css" />
      @endonce

      <div class="header-container-2">
        <div
          data-animation="default"
          data-collapse="tiny"
          data-duration="400"
          data-easing="ease"
          data-easing2="ease"
          role="banner"
          class="navbar w-nav"
        >
          <div class="container-default-2 w-container">
            <div class="header-container-wrapper-2">
              <div class="div-block-17 phone3">
                <div class="link-block odsf">
                  <img
                    src="/webflow-assets/images/offer-icon.svg"
                    width="18"
                    height="18"
                    loading="lazy"
                    alt=""
                    class="special-offers-icon"
                  />
                  <div class="text-block-17">
                    <span class="link-10">Special Offers</span>
                  </div>
                </div>
              </div>
              <div class="div-block-17">
                <div class="link-block">
                  <div class="text-block-17">We are – 100% employee owned &amp; over 30 years in business!</div>
                </div>
              </div>
              <div class="nav-menu-left-side-2"></div>
              <div class="div-block-16"><div class="text-block-15">{{ site_phone_display() }}</div></div>
            </div>
          </div>
          <div
            class="w-nav-overlay"
            data-wf-ignore=""
            id="w-nav-overlay-0"
            style="pointer-events: none; display: none"
          ></div>
        </div>
        <div
          data-animation="default"
          data-collapse="medium"
          data-duration="400"
          data-easing="ease"
          data-easing2="ease"
          role="banner"
          class="header-wrapper-2 w-nav"
        >
          <div class="container-default-2 w-container">
            <div class="header-container-wrapper-2">
              <div class="nav-menu-left-side-2">
                <div class="logo-wrapper v1">
                  <a
                    href="/"
                    aria-current="page"
                    class="logo-link-2 w-inline-block w--current"
                    ><x-img
                      src="/webflow-assets/images/686acba4611e759fd8169f9d_photo_2025-07-06-22.14.41.avif"
                      preset="nav_logo"
                      alt="Deluxe Windows"
                      class="image-14"
                  /></a>
                </div>
                <nav role="navigation" class="nav-menu-wrapper-2 w-nav-menu">
                  <ul role="list" class="list-nav-menu-2">
                    <li class="link-nav-item">
                      <div
                        data-delay="0"
                        data-hover="true"
                        data-w-id="632206d9-3c73-e5be-be34-15992c2e833a"
                        class="dropdown-wrapper dropdown-default w-dropdown"
                      >
                        <div
                          class="dropdown-toogle-2 w-dropdown-toggle"
                          id="w-dropdown-toggle-0"
                          aria-controls="w-dropdown-list-0"
                          aria-haspopup="menu"
                          aria-expanded="false"
                          role="button"
                          tabindex="0"
                        >
                          <div>Windows</div>
                          <div
                            class="icon-font-rounded-2 dropdown-arrow"
                            style="
                              transform: translate3d(0px, 0px, 0px) scale3d(1, 1, 1) rotateX(0deg) rotateY(0deg)
                                rotateZ(0deg) skew(0deg, 0deg);
                              transform-style: preserve-3d;
                            "
                          >
                            
                          </div>
                        </div>
                        <nav
                          class="dropdown-list-2 dropdown-v1 w-dropdown-list"
                          id="w-dropdown-list-0"
                          aria-labelledby="w-dropdown-toggle-0"
                          style="display: none; height: 0px; opacity: 0"
                        >
                          <div
                            class="dropdown-pd-2 dropdown-v4"
                            style="
                              transform: translate3d(0px, 10px, 0px) scale3d(1, 1, 1) rotateX(0deg) rotateY(0deg)
                                rotateZ(0deg) skew(0deg, 0deg);
                              transform-style: preserve-3d;
                            "
                          >
                            <div class="w-layout-grid grid-2-columns-2 dropdown-link-column v4">
                              <div id="w-node-_632206d9-3c73-e5be-be34-15992c2e8343-2c2e830e">
                                <a
                                  href="/windows"
                                  class="dropdown-link-2 dropdown-link-title"
                                  tabindex="0"
                                  >Materials</a
                                >
                                <div class="w-layout-grid grid-1-column-2 dropdown-link-column">
                                  <a
                                    href="/windows"
                                    id="w-node-_632206d9-3c73-e5be-be34-15992c2e8347-2c2e830e"
                                    class="dropdown-link-2"
                                    tabindex="0"
                                    >All</a
                                  ><a
                                    id="w-node-_632206d9-3c73-e5be-be34-15992c2e8349-2c2e830e"
                                    href="/windows/vinyl-windows"
                                    class="dropdown-link-2"
                                    tabindex="0"
                                    >Vinyl</a
                                  ><a
                                    href="/windows/wood-clad-windows"
                                    id="w-node-_632206d9-3c73-e5be-be34-15992c2e834b-2c2e830e"
                                    class="dropdown-link-2"
                                    tabindex="0"
                                    >Wood clad</a
                                  ><a
                                    id="w-node-_632206d9-3c73-e5be-be34-15992c2e834d-2c2e830e"
                                    href="/windows/fiberglass-windows"
                                    class="dropdown-link-2"
                                    tabindex="0"
                                    >Fiberglass</a
                                  ><a
                                    id="w-node-_632206d9-3c73-e5be-be34-15992c2e834f-2c2e830e"
                                    href="/windows/wood-windows"
                                    class="dropdown-link-2"
                                    tabindex="0"
                                    >Wood</a
                                  ><a
                                    id="w-node-_632206d9-3c73-e5be-be34-15992c2e8351-2c2e830e"
                                    href="/windows/aluminum-windows"
                                    class="dropdown-link-2"
                                    tabindex="0"
                                    >Aluminum</a
                                  ><a
                                    id="w-node-_174a8520-3147-7d0b-af22-074f0a21cbcc-2c2e830e"
                                    href="/windows/aluminum-clad-windows"
                                    class="dropdown-link-2"
                                    tabindex="0"
                                    >Aluminum Clad</a
                                  ><a
                                    id="w-node-_632206d9-3c73-e5be-be34-15992c2e8353-2c2e830e"
                                    href="/windows/steel-windows"
                                    class="dropdown-link-2"
                                    tabindex="0"
                                    >Steel</a
                                  >
                                </div>
                              </div>
                              <div id="w-node-_632206d9-3c73-e5be-be34-15992c2e8355-2c2e830e">
                                <a
                                  href="/brand"
                                  class="dropdown-link-2 dropdown-link-title"
                                  tabindex="0"
                                  >Brands</a
                                >
                                <div class="w-layout-grid main-pages-2">
                                  <div class="w-layout-grid grid-1-column-2 dropdown-link-column small">
                                    <a
                                      href="/brand"
                                      id="w-node-_632206d9-3c73-e5be-be34-15992c2e835a-2c2e830e"
                                      class="dropdown-link-2"
                                      tabindex="0"
                                      >All</a
                                    ><a
                                      id="w-node-_632206d9-3c73-e5be-be34-15992c2e835c-2c2e830e"
                                      href="/brands/marvin"
                                      class="dropdown-link-2"
                                      tabindex="0"
                                      >Marvin</a
                                    ><a
                                      id="w-node-_632206d9-3c73-e5be-be34-15992c2e835e-2c2e830e"
                                      href="/brands/milgard"
                                      class="dropdown-link-2"
                                      tabindex="0"
                                      >Milgard</a
                                    ><a
                                      id="w-node-_632206d9-3c73-e5be-be34-15992c2e8360-2c2e830e"
                                      href="/brands/jeld-wen"
                                      class="dropdown-link-2"
                                      tabindex="0"
                                      >Jeld-Wen</a
                                    ><a
                                      id="w-node-_632206d9-3c73-e5be-be34-15992c2e8362-2c2e830e"
                                      href="/brands/anlin"
                                      class="dropdown-link-2"
                                      tabindex="0"
                                      >Anlin</a
                                    ><a
                                      id="w-node-_632206d9-3c73-e5be-be34-15992c2e8364-2c2e830e"
                                      href="/brands/italwindows"
                                      class="dropdown-link-2"
                                      tabindex="0"
                                      >Italwindows</a
                                    >
                                  </div>
                                  <div
                                    id="w-node-_632206d9-3c73-e5be-be34-15992c2e8366-2c2e830e"
                                    class="w-layout-grid grid-1-column-2 dropdown-link-column small"
                                  >
                                    <a
                                      id="w-node-_632206d9-3c73-e5be-be34-15992c2e8367-2c2e830e"
                                      href="/brands/andersen"
                                      class="dropdown-link-2"
                                      tabindex="0"
                                      >Andersen</a
                                    ><a
                                      id="w-node-_632206d9-3c73-e5be-be34-15992c2e8369-2c2e830e"
                                      href="/brands/ply-gem"
                                      class="dropdown-link-2"
                                      tabindex="0"
                                      >Ply Gem</a
                                    ><a
                                      id="w-node-_632206d9-3c73-e5be-be34-15992c2e836b-2c2e830e"
                                      href="/brands/simonton"
                                      class="dropdown-link-2"
                                      tabindex="0"
                                      >Simonton</a
                                    ><a
                                      id="w-node-_632206d9-3c73-e5be-be34-15992c2e836d-2c2e830e"
                                      href="/brands/alside"
                                      class="dropdown-link-2"
                                      tabindex="0"
                                      >Alside</a
                                    ><a
                                      id="w-node-_632206d9-3c73-e5be-be34-15992c2e836f-2c2e830e"
                                      href="/brands/western-window-systems"
                                      class="dropdown-link-2"
                                      tabindex="0"
                                      >Western Window Systems</a
                                    ><a
                                      id="w-node-_632206d9-3c73-e5be-be34-15992c2e8371-2c2e830e"
                                      href="/brands/all-weather-architectural-aluminum"
                                      class="dropdown-link-2"
                                      tabindex="0"
                                      >All Weather Architectural Aluminum</a
                                    >
                                  </div>
                                </div>
                              </div>
                              <div
                                id="w-node-_632206d9-3c73-e5be-be34-15992c2e8373-2c2e830e"
                                class="mega-menu-banner w-clearfix"
                              >
                                <x-img
                                  alt="{{ promotion_percent_label() }} Windows"
                                  src="/webflow-assets/images/6862abfc8f55643864a69255_Frame%201.avif"
                                  preset="card_sm"
                                  loading="lazy"
                                  class="image-14 cover-image property-wrapper-v3---image"
                                />
                                <div class="w-layout-blockcontainer container-2 w-container">
                                  <h5>{{ promotion_percent_label() }} Windows</h5>
                                  <div class="text-regular-2">
                                    Offer ends <span class="date-span">{{ promotion_date('us-short-no-year') }}</span
                                    ><br />
                                  </div>
                                  <a
                                    href="/special-offers"
                                    id="w-node-_632206d9-3c73-e5be-be34-15992c2e837b-2c2e830e"
                                    class="primary-button-2 w-inline-block"
                                    tabindex="0"
                                    ><div>Learn More</div></a
                                  >
                                </div>
                              </div>
                            </div>
                          </div>
                        </nav>
                      </div>
                    </li>
                    <li class="link-nav-item">
                      <div
                        data-delay="0"
                        data-hover="true"
                        data-w-id="632206d9-3c73-e5be-be34-15992c2e837f"
                        class="dropdown-wrapper dropdown-default w-dropdown"
                      >
                        <div
                          class="dropdown-toogle-2 w-dropdown-toggle"
                          id="w-dropdown-toggle-1"
                          aria-controls="w-dropdown-list-1"
                          aria-haspopup="menu"
                          aria-expanded="false"
                          role="button"
                          tabindex="0"
                        >
                          <div>Doors</div>
                          <div
                            class="icon-font-rounded-2 dropdown-arrow"
                            style="
                              transform: translate3d(0px, 0px, 0px) scale3d(1, 1, 1) rotateX(0deg) rotateY(0deg)
                                rotateZ(0deg) skew(0deg, 0deg);
                              transform-style: preserve-3d;
                            "
                          >
                            
                          </div>
                        </div>
                        <nav
                          class="dropdown-list-2 dropdown-v1 w-dropdown-list"
                          id="w-dropdown-list-1"
                          aria-labelledby="w-dropdown-toggle-1"
                          style="display: none; height: 0px; opacity: 0"
                        >
                          <div
                            class="dropdown-pd-2 dropdown-v4"
                            style="
                              transform: translate3d(0px, 10px, 0px) scale3d(1, 1, 1) rotateX(0deg) rotateY(0deg)
                                rotateZ(0deg) skew(0deg, 0deg);
                              transform-style: preserve-3d;
                            "
                          >
                            <div class="w-layout-grid grid-2-columns-2 dropdown-link-column v4">
                              <div id="w-node-_632206d9-3c73-e5be-be34-15992c2e8388-2c2e830e">
                                <a
                                  href="/doors"
                                  class="dropdown-link-2 dropdown-link-title"
                                  tabindex="0"
                                  >Materials</a
                                >
                                <div class="w-layout-grid grid-1-column-2 dropdown-link-column">
                                  <a
                                    href="/doors"
                                    id="w-node-_632206d9-3c73-e5be-be34-15992c2e838c-2c2e830e"
                                    class="dropdown-link-2"
                                    tabindex="0"
                                    >All</a
                                  ><a
                                    id="w-node-_632206d9-3c73-e5be-be34-15992c2e838e-2c2e830e"
                                    href="/doors/vinyl-doors"
                                    class="dropdown-link-2"
                                    tabindex="0"
                                    >Vinyl</a
                                  ><a
                                    href="/doors/wood-clad-doors"
                                    id="w-node-_632206d9-3c73-e5be-be34-15992c2e8390-2c2e830e"
                                    class="dropdown-link-2"
                                    tabindex="0"
                                    >Wood clad</a
                                  ><a
                                    id="w-node-_632206d9-3c73-e5be-be34-15992c2e8392-2c2e830e"
                                    href="/doors/fiberglass-doors"
                                    class="dropdown-link-2"
                                    tabindex="0"
                                    >Fiberglass</a
                                  ><a
                                    id="w-node-_632206d9-3c73-e5be-be34-15992c2e8394-2c2e830e"
                                    href="/doors/wood-doors"
                                    class="dropdown-link-2"
                                    tabindex="0"
                                    >Wood</a
                                  ><a
                                    id="w-node-_632206d9-3c73-e5be-be34-15992c2e8396-2c2e830e"
                                    href="/doors/aluminum-doors"
                                    class="dropdown-link-2"
                                    tabindex="0"
                                    >Aluminum</a
                                  ><a
                                    id="w-node-_632206d9-3c73-e5be-be34-15992c2e8398-2c2e830e"
                                    href="/doors/vinyl-doors"
                                    class="dropdown-link-2"
                                    tabindex="0"
                                    >Steel</a
                                  >
                                </div>
                              </div>
                              <div id="w-node-_632206d9-3c73-e5be-be34-15992c2e839a-2c2e830e">
                                <a
                                  href="/brand"
                                  class="dropdown-link-2 dropdown-link-title"
                                  tabindex="0"
                                  >Brands</a
                                >
                                <div class="w-layout-grid main-pages-2">
                                  <div class="w-layout-grid grid-1-column-2 dropdown-link-column small">
                                    <a
                                      href="/brand"
                                      id="w-node-_632206d9-3c73-e5be-be34-15992c2e839f-2c2e830e"
                                      class="dropdown-link-2"
                                      tabindex="0"
                                      >All</a
                                    ><a
                                      id="w-node-_632206d9-3c73-e5be-be34-15992c2e83a1-2c2e830e"
                                      href="/brands/marvin"
                                      class="dropdown-link-2"
                                      tabindex="0"
                                      >Marvin</a
                                    ><a
                                      id="w-node-_632206d9-3c73-e5be-be34-15992c2e83a3-2c2e830e"
                                      href="/brands/milgard"
                                      class="dropdown-link-2"
                                      tabindex="0"
                                      >Milgard</a
                                    ><a
                                      id="w-node-_632206d9-3c73-e5be-be34-15992c2e83a5-2c2e830e"
                                      href="/brands/jeld-wen"
                                      class="dropdown-link-2"
                                      tabindex="0"
                                      >Jeld-Wen</a
                                    ><a
                                      id="w-node-_632206d9-3c73-e5be-be34-15992c2e83a7-2c2e830e"
                                      href="/brands/anlin"
                                      class="dropdown-link-2"
                                      tabindex="0"
                                      >Anlin</a
                                    ><a
                                      id="w-node-_632206d9-3c73-e5be-be34-15992c2e83a9-2c2e830e"
                                      href="/brands/italwindows"
                                      class="dropdown-link-2"
                                      tabindex="0"
                                      >Italwindows</a
                                    >
                                  </div>
                                  <div
                                    id="w-node-_632206d9-3c73-e5be-be34-15992c2e83ab-2c2e830e"
                                    class="w-layout-grid grid-1-column-2 dropdown-link-column small"
                                  >
                                    <a
                                      id="w-node-_632206d9-3c73-e5be-be34-15992c2e83ac-2c2e830e"
                                      href="/brands/andersen"
                                      class="dropdown-link-2"
                                      tabindex="0"
                                      >Andersen</a
                                    ><a
                                      id="w-node-_632206d9-3c73-e5be-be34-15992c2e83ae-2c2e830e"
                                      href="/brands/ply-gem"
                                      class="dropdown-link-2"
                                      tabindex="0"
                                      >Ply Gem</a
                                    ><a
                                      id="w-node-_632206d9-3c73-e5be-be34-15992c2e83b0-2c2e830e"
                                      href="/brands/simonton"
                                      class="dropdown-link-2"
                                      tabindex="0"
                                      >Simonton</a
                                    ><a
                                      id="w-node-_632206d9-3c73-e5be-be34-15992c2e83b2-2c2e830e"
                                      href="/brands/alside"
                                      class="dropdown-link-2"
                                      tabindex="0"
                                      >Alside</a
                                    ><a
                                      id="w-node-_632206d9-3c73-e5be-be34-15992c2e83b4-2c2e830e"
                                      href="/brands/western-window-systems"
                                      class="dropdown-link-2"
                                      tabindex="0"
                                      >Western Window Systems</a
                                    ><a
                                      id="w-node-_632206d9-3c73-e5be-be34-15992c2e83b6-2c2e830e"
                                      href="/brands/all-weather-architectural-aluminum"
                                      class="dropdown-link-2"
                                      tabindex="0"
                                      >All Weather Architectural Aluminum</a
                                    >
                                  </div>
                                </div>
                              </div>
                              <div
                                id="w-node-_632206d9-3c73-e5be-be34-15992c2e83b8-2c2e830e"
                                class="mega-menu-banner w-clearfix"
                              >
                                <x-img
                                  alt="{{ promotion_percent_label() }} Doors"
                                  src="/webflow-assets/images/6845713efb9f90434a8611f6_4.avif"
                                  preset="card_sm"
                                  loading="lazy"
                                  class="image-14 cover-image property-wrapper-v3---image"
                                />
                                <div class="w-layout-blockcontainer container-2 w-container">
                                  <h5>{{ promotion_percent_label() }} Doors</h5>
                                  <div class="text-regular-2">
                                    Offer ends <span class="date-span">{{ promotion_date('us-short-no-year') }}</span
                                    ><br />
                                  </div>
                                  <a
                                    href="/special-offers"
                                    id="w-node-_632206d9-3c73-e5be-be34-15992c2e83c0-2c2e830e"
                                    class="primary-button-2 w-inline-block"
                                    tabindex="0"
                                    ><div>Learn More</div></a
                                  >
                                </div>
                              </div>
                            </div>
                          </div>
                        </nav>
                      </div>
                    </li>
                    <li class="link-nav-item">
                      <div
                        data-delay="0"
                        data-hover="true"
                        data-w-id="632206d9-3c73-e5be-be34-15992c2e83c4"
                        class="dropdown-wrapper dropdown-default w-dropdown"
                      >
                        <div
                          class="dropdown-toogle-2 w-dropdown-toggle"
                          id="w-dropdown-toggle-2"
                          aria-controls="w-dropdown-list-2"
                          aria-haspopup="menu"
                          aria-expanded="false"
                          role="button"
                          tabindex="0"
                        >
                          <div>Learning Center</div>
                          <div
                            class="icon-font-rounded-2 dropdown-arrow"
                            style="
                              transform: translate3d(0px, 0px, 0px) scale3d(1, 1, 1) rotateX(0deg) rotateY(0deg)
                                rotateZ(0deg) skew(0deg, 0deg);
                              transform-style: preserve-3d;
                            "
                          >
                            
                          </div>
                        </div>
                        <nav
                          class="dropdown-list-2 dropdown-v1 w-dropdown-list"
                          id="w-dropdown-list-2"
                          aria-labelledby="w-dropdown-toggle-2"
                          style="display: none; height: 0px; opacity: 0"
                        >
                          <div
                            class="dropdown-pd-2 dropdown-v4"
                            style="
                              transform: translate3d(0px, 10px, 0px) scale3d(1, 1, 1) rotateX(0deg) rotateY(0deg)
                                rotateZ(0deg) skew(0deg, 0deg);
                              transform-style: preserve-3d;
                            "
                          >
                            <div class="w-layout-grid grid-2-columns-2 dropdown-link-column v4">
                              <div id="w-node-_632206d9-3c73-e5be-be34-15992c2e83cd-2c2e830e">
                                <div class="w-layout-grid grid-1-column-2 dropdown-link-column">
                                  <a
                                    id="w-node-_632206d9-3c73-e5be-be34-15992c2e83cf-2c2e830e"
                                    href="/blog"
                                    class="dropdown-link-2"
                                    tabindex="0"
                                    >Knowledge Articles</a
                                  ><a
                                    href="/blog/how-to-measure-windows-for-replacement"
                                    id="w-node-_632206d9-3c73-e5be-be34-15992c2e83d1-2c2e830e"
                                    class="dropdown-link-2-copy"
                                    tabindex="0"
                                    >Window Measurement Guide</a
                                  ><a
                                    href="/blog/comprehensive-guide-to-choosing-the-right-replacement-windows"
                                    id="w-node-_632206d9-3c73-e5be-be34-15992c2e83d3-2c2e830e"
                                    class="dropdown-link-2-copy"
                                    tabindex="0"
                                    >Tips for Windows Replacement </a
                                  ><a
                                    href="/blog/window-buyers-guide"
                                    id="w-node-_632206d9-3c73-e5be-be34-15992c2e83d5-2c2e830e"
                                    target="_blank"
                                    class="dropdown-link-2-copy"
                                    tabindex="0"
                                    >Window Buyer's Guide</a
                                  ><a
                                    href="/blog/the-ultimate-door-buyers-guide"
                                    id="w-node-_632206d9-3c73-e5be-be34-15992c2e83d7-2c2e830e"
                                    class="dropdown-link-2-copy"
                                    tabindex="0"
                                    >Door Buyer's Guide</a
                                  ><a
                                    href="/glossary"
                                    id="w-node-_632206d9-3c73-e5be-be34-15992c2e83d9-2c2e830e"
                                    class="dropdown-link-2"
                                    tabindex="0"
                                    >Glossary</a
                                  ><a
                                    href="/faq"
                                    id="w-node-_632206d9-3c73-e5be-be34-15992c2e83db-2c2e830e"
                                    class="dropdown-link-2"
                                    tabindex="0"
                                    >Frequently Asked Questions</a
                                  >
                                </div>
                              </div>
                              <div id="w-node-_632206d9-3c73-e5be-be34-15992c2e83dd-2c2e830e">
                                <div class="w-layout-grid main-pages-2">
                                  <div class="w-layout-grid grid-1-column-2 dropdown-link-column small"></div>
                                  <div
                                    id="w-node-_632206d9-3c73-e5be-be34-15992c2e83e0-2c2e830e"
                                    class="w-layout-grid grid-1-column-2 dropdown-link-column small"
                                  ></div>
                                </div>
                              </div>
                              <div
                                id="w-node-_632206d9-3c73-e5be-be34-15992c2e83e1-2c2e830e"
                                class="mega-menu-banner w-clearfix"
                              >
                                <x-img
                                  alt="{{ promotion_percent_label() }} Windows"
                                  src="/webflow-assets/images/6862abfc8f55643864a69255_Frame%201.avif"
                                  preset="card_sm"
                                  loading="lazy"
                                  class="image-14 cover-image property-wrapper-v3---image"
                                />
                                <div class="w-layout-blockcontainer container-2 w-container">
                                  <h5>{{ promotion_percent_label() }} Windows</h5>
                                  <div class="text-regular-2">
                                    Offer ends <span class="date-span">{{ promotion_date('us-short-no-year') }}</span
                                    ><br />
                                  </div>
                                  <a
                                    href="/special-offers"
                                    id="w-node-_632206d9-3c73-e5be-be34-15992c2e83e9-2c2e830e"
                                    class="primary-button-2 w-inline-block"
                                    tabindex="0"
                                    ><div>Learn More</div></a
                                  >
                                </div>
                              </div>
                            </div>
                          </div>
                        </nav>
                      </div>
                    </li>
                    <li class="link-nav-item">
                      <div
                        data-delay="0"
                        data-hover="true"
                        data-w-id="632206d9-3c73-e5be-be34-15992c2e83ed"
                        class="dropdown-wrapper dropdown-default w-dropdown"
                      >
                        <div
                          class="dropdown-toogle-2 w-dropdown-toggle"
                          id="w-dropdown-toggle-3"
                          aria-controls="w-dropdown-list-3"
                          aria-haspopup="menu"
                          aria-expanded="false"
                          role="button"
                          tabindex="0"
                        >
                          <div>Resources &amp; Support</div>
                          <div
                            class="icon-font-rounded-2 dropdown-arrow"
                            style="
                              transform: translate3d(0px, 0px, 0px) scale3d(1, 1, 1) rotateX(0deg) rotateY(0deg)
                                rotateZ(0deg) skew(0deg, 0deg);
                              transform-style: preserve-3d;
                            "
                          >
                            
                          </div>
                        </div>
                        <nav
                          class="dropdown-list-2 dropdown-v1 w-dropdown-list"
                          id="w-dropdown-list-3"
                          aria-labelledby="w-dropdown-toggle-3"
                          style="display: none; height: 0px; opacity: 0"
                        >
                          <div
                            class="dropdown-pd-2 dropdown-v4"
                            style="
                              transform: translate3d(0px, 10px, 0px) scale3d(1, 1, 1) rotateX(0deg) rotateY(0deg)
                                rotateZ(0deg) skew(0deg, 0deg);
                              transform-style: preserve-3d;
                            "
                          >
                            <div class="w-layout-grid grid-2-columns-2 dropdown-link-column v4">
                              <div id="w-node-_632206d9-3c73-e5be-be34-15992c2e83f6-2c2e830e">
                                <div class="w-layout-grid grid-1-column-2 dropdown-link-column">
                                  <a
                                    href="/#"
                                    class="dropdown-link-2 dropdown-link-title"
                                    tabindex="0"
                                    >Resources</a
                                  ><a
                                    href="/special-offers"
                                    id="w-node-_632206d9-3c73-e5be-be34-15992c2e83f8-2c2e830e"
                                    class="dropdown-link-2"
                                    tabindex="0"
                                    >Special Offers</a
                                  ><a
                                    href="/financing"
                                    id="w-node-_632206d9-3c73-e5be-be34-15992c2e83fa-2c2e830e"
                                    class="dropdown-link-2"
                                    tabindex="0"
                                    >Financing</a
                                  ><a
                                    id="w-node-_632206d9-3c73-e5be-be34-15992c2e83fe-2c2e830e"
                                    href="/gallery"
                                    class="dropdown-link-2"
                                    tabindex="0"
                                    >Gallery</a
                                  ><a
                                    href="/about"
                                    id="w-node-_632206d9-3c73-e5be-be34-15992c2e8400-2c2e830e"
                                    class="dropdown-link-2"
                                    tabindex="0"
                                    >About Us</a
                                  ><a
                                    href="/contacts"
                                    id="w-node-_632206d9-3c73-e5be-be34-15992c2e8402-2c2e830e"
                                    class="dropdown-link-2"
                                    tabindex="0"
                                    >Contact Us</a
                                  ><a
                                    href="/testimonials"
                                    id="w-node-_632206d9-3c73-e5be-be34-15992c2e8404-2c2e830e"
                                    class="dropdown-link-2"
                                    tabindex="0"
                                    >Testimonials</a
                                  >
                                </div>
                              </div>
                              <div id="w-node-_632206d9-3c73-e5be-be34-15992c2e8406-2c2e830e">
                                <a
                                  href="/brand"
                                  class="dropdown-link-2 dropdown-link-title"
                                  tabindex="0"
                                  >Premium Service Areas</a
                                >
                                <div class="w-layout-grid main-pages-2">
                                  <div class="w-dyn-list">
                                    <div role="list" class="w-dyn-items">
                                      <div role="listitem" class="w-dyn-item">
                                        <a
                                          href="/county-hub-pages/san-francisco-county"
                                          class="dropdown-link-2"
                                          tabindex="0"
                                          >San Francisco County</a
                                        >
                                      </div>
                                      <div role="listitem" class="w-dyn-item">
                                        <a
                                          href="/county-hub-pages/napa-county"
                                          class="dropdown-link-2"
                                          tabindex="0"
                                          >Napa County</a
                                        >
                                      </div>
                                      <div role="listitem" class="w-dyn-item">
                                        <a
                                          href="/county-hub-pages/sonoma-county"
                                          class="dropdown-link-2"
                                          tabindex="0"
                                          >Sonoma County</a
                                        >
                                      </div>
                                      <div role="listitem" class="w-dyn-item">
                                        <a
                                          href="/county-hub-pages/solano-county"
                                          class="dropdown-link-2"
                                          tabindex="0"
                                          >Solano County</a
                                        >
                                      </div>
                                      <div role="listitem" class="w-dyn-item">
                                        <a
                                          href="/county-hub-pages/marin-county"
                                          class="dropdown-link-2"
                                          tabindex="0"
                                          >Marin County</a
                                        >
                                      </div>
                                      <div role="listitem" class="w-dyn-item">
                                        <a
                                          href="/county-hub-pages/san-mateo-county"
                                          class="dropdown-link-2"
                                          tabindex="0"
                                          >San Mateo County</a
                                        >
                                      </div>
                                      <div role="listitem" class="w-dyn-item">
                                        <a
                                          href="/county-hub-pages/santa-clara-county"
                                          class="dropdown-link-2"
                                          tabindex="0"
                                          >Santa Clara County</a
                                        >
                                      </div>
                                      <div role="listitem" class="w-dyn-item">
                                        <a
                                          href="/county-hub-pages/contra-costa-county"
                                          class="dropdown-link-2"
                                          tabindex="0"
                                          >Contra Costa County</a
                                        >
                                      </div>
                                      <div role="listitem" class="w-dyn-item">
                                        <a
                                          href="/county-hub-pages/alameda-county"
                                          class="dropdown-link-2"
                                          tabindex="0"
                                          >Alameda County</a
                                        >
                                      </div>
                                    </div>
                                  </div>
                                </div>
                              </div>
                              <div
                                id="w-node-_632206d9-3c73-e5be-be34-15992c2e840a-2c2e830e"
                                class="mega-menu-banner w-clearfix"
                              >
                                <x-img
                                  alt="{{ promotion_percent_label() }} Windows"
                                  src="/webflow-assets/images/6862abfc8f55643864a69255_Frame%201.avif"
                                  preset="card_sm"
                                  loading="lazy"
                                  class="image-14 cover-image property-wrapper-v3---image"
                                />
                                <div class="w-layout-blockcontainer container-2 w-container">
                                  <h5>{{ promotion_percent_label() }} Windows</h5>
                                  <div class="text-regular-2">
                                    Offer ends <span class="date-span">{{ promotion_date('us-short-no-year') }}</span
                                    ><br />
                                  </div>
                                  <a
                                    href="/special-offers"
                                    id="w-node-_632206d9-3c73-e5be-be34-15992c2e8412-2c2e830e"
                                    class="primary-button-2 w-inline-block"
                                    tabindex="0"
                                    ><div>Learn More</div></a
                                  >
                                </div>
                              </div>
                            </div>
                          </div>
                        </nav>
                      </div>
                    </li>
                  </ul>
                </nav>
              </div>
              <div class="nav-menu-right-side-2">
                <a
                  href="#"
                  id="w-node-_632206d9-3c73-e5be-be34-15992c2e8416-2c2e830e"
                  class="primary-button-2 w-inline-block"
                  data-open-estimate-modal
                  ><div>Request a Free Estimate</div></a
                >
                <div
                  class="hamburger-menu-2 w-nav-button"
                  style="-webkit-user-select: text"
                  aria-label="menu"
                  role="button"
                  tabindex="0"
                  aria-controls="w-nav-overlay-1"
                  aria-haspopup="menu"
                  aria-expanded="false"
                >
                  <div class="hamburger-menu-flex-2">
                    <div class="hamburger-menu-line-2 middle"></div>
                    <div class="hamburger-menu-line-2 middle"></div>
                    <div class="hamburger-menu-line-2 middle"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="w-nav-overlay" data-wf-ignore="" id="w-nav-overlay-1"></div>
        </div>
      </div>

      <div class="mobile-header-shell">
        <div class="mobile-top-strip" aria-label="Company info">
          <div class="mobile-top-strip__inner">
            <span class="mobile-top-strip__text">We are – 100% employee owned &amp; over 30 years in business!</span>
          </div>
        </div>
        <div class="navbar-3">
          <div
            data-animation="default"
            data-collapse="medium"
            data-duration="400"
            data-easing="ease"
            data-easing2="ease"
            role="banner"
            class="navbar-container w-nav"
          >
            <div class="container-regular">
              <div class="navbar-wrapper">
                <a
                  href="/"
                  aria-current="page"
                  class="navbar-brand w-nav-brand w--current"
                  aria-label="home"
                  ><x-img
                    width="283"
                    loading="lazy"
                    alt="Deluxe Windows"
                    src="/webflow-assets/images/686acba4611e759fd8169f9d_photo_2025-07-06-22.14.41.avif"
                    preset="nav_logo"
                    class="image-24" /></a
                ><a href="tel:{{ site_phone_tel() }}" class="link-15" aria-label="Call {{ site_phone_display() }}"
                  ><img
                    src="/webflow-assets/images/687559a123cece2e95a41a6f_phone_enabled_24dp_FFFFFF_FILL1_wght400_GRAD0_opsz24.svg"
                    alt=""
                    loading="lazy"
                    class="link-15__icon"
                  /><span class="link-15__label">{{ site_phone_display() }}</span></a
                >
                <nav role="navigation" class="nav-menu-wrapper-4 w-nav-menu">
                  <ul role="list" class="nav-menu-2 w-list-unstyled">
                    <li class="menu">
                      <div data-delay="0" data-hover="false" class="nav-dropdown w-dropdown">
                        <div
                          class="nav-dropdown-toggle-2 w-dropdown-toggle"
                          id="w-dropdown-toggle-4"
                          aria-controls="w-dropdown-list-4"
                          aria-haspopup="menu"
                          aria-expanded="false"
                          role="button"
                          tabindex="0"
                        >
                          <div class="nav-dropdown-icon-2 w-icon-dropdown-toggle" aria-hidden="true"></div>
                          <div class="text-block-31">Windows</div>
                        </div>
                        <nav
                          class="nav-dropdown-list-2 shadow-three mobile-shadow-hide w-dropdown-list"
                          id="w-dropdown-list-4"
                          aria-labelledby="w-dropdown-toggle-4"
                        >
                          <div data-delay="0" data-hover="false" class="nav-dropdown w-dropdown">
                            <div
                              class="nav-dropdown-toggle-2 w-dropdown-toggle"
                              id="w-dropdown-toggle-5"
                              aria-controls="w-dropdown-list-5"
                              aria-haspopup="menu"
                              aria-expanded="false"
                              role="button"
                              tabindex="0"
                            >
                              <div class="nav-dropdown-icon-2 w-icon-dropdown-toggle" aria-hidden="true"></div>
                              <div>Materials</div>
                            </div>
                            <nav
                              class="nav-dropdown-list-2 shadow-three mobile-shadow-hide w-dropdown-list"
                              id="w-dropdown-list-5"
                              aria-labelledby="w-dropdown-toggle-5"
                            >
                              <a
                                href="/windows"
                                class="nav-dropdown-link-5 w-dropdown-link"
                                >All</a
                              ><a
                                href="/windows/vinyl-windows"
                                class="nav-dropdown-link-5 w-dropdown-link"
                                >Vinyl</a
                              ><a
                                href="/windows/wood-clad-windows"
                                class="nav-dropdown-link-5 w-dropdown-link"
                                >Wood clad</a
                              ><a
                                href="/windows/fiberglass-windows"
                                class="nav-dropdown-link-5 w-dropdown-link"
                                >Fiberglass</a
                              ><a
                                href="/windows/wood-windows"
                                class="nav-dropdown-link-5 w-dropdown-link"
                                >Wood</a
                              ><a
                                href="/windows/aluminum-windows"
                                class="nav-dropdown-link-5 w-dropdown-link"
                                >Aluminium</a
                              ><a
                                href="/windows/steel-windows"
                                class="nav-dropdown-link-5 w-dropdown-link"
                                >Steel</a
                              ><a
                                href="/windows/aluminum-clad-windows"
                                class="nav-dropdown-link-5 w-dropdown-link"
                                >Aluminum Clad</a
                              >
                            </nav>
                          </div>
                          <div data-delay="0" data-hover="false" class="nav-dropdown w-dropdown">
                            <div
                              class="nav-dropdown-toggle-2 w-dropdown-toggle"
                              id="w-dropdown-toggle-6"
                              aria-controls="w-dropdown-list-6"
                              aria-haspopup="menu"
                              aria-expanded="false"
                              role="button"
                              tabindex="0"
                            >
                              <div class="nav-dropdown-icon-2 w-icon-dropdown-toggle" aria-hidden="true"></div>
                              <div>Brands</div>
                            </div>
                            <nav
                              class="nav-dropdown-list-2 shadow-three mobile-shadow-hide w-dropdown-list"
                              id="w-dropdown-list-6"
                              aria-labelledby="w-dropdown-toggle-6"
                            >
                              <a href="/brand" class="nav-dropdown-link-5 w-dropdown-link"
                                >All</a
                              ><a
                                href="/brands/marvin"
                                class="nav-dropdown-link-5 w-dropdown-link"
                                >Marvin</a
                              ><a
                                href="/brands/milgard"
                                class="nav-dropdown-link-5 w-dropdown-link"
                                >Milgard</a
                              ><a
                                href="/brands/jeld-wen"
                                class="nav-dropdown-link-5 w-dropdown-link"
                                >Jeld-Wen</a
                              ><a
                                href="/brands/anlin"
                                class="nav-dropdown-link-5 w-dropdown-link"
                                >Anlin</a
                              ><a
                                href="/brands/italwindows"
                                class="nav-dropdown-link-5 w-dropdown-link"
                                >Italwindows</a
                              ><a
                                href="/brands/andersen"
                                class="nav-dropdown-link-5 w-dropdown-link"
                                >Andersen</a
                              ><a
                                href="/brands/ply-gem"
                                class="nav-dropdown-link-5 w-dropdown-link"
                                >Ply Gem</a
                              ><a
                                href="/brands/simonton"
                                class="nav-dropdown-link-5 w-dropdown-link"
                                >Simonton</a
                              ><a
                                href="/brands/alside"
                                class="nav-dropdown-link-5 w-dropdown-link"
                                >Alside</a
                              ><a
                                href="/brands/western-window-systems"
                                class="nav-dropdown-link-5 w-dropdown-link"
                                >Western Window Systems</a
                              ><a
                                href="/brands/all-weather-architectural-aluminum"
                                class="nav-dropdown-link-5 w-dropdown-link"
                                >All Weather Architectural Aluminum</a
                              >
                            </nav>
                          </div>
                        </nav>
                      </div>
                    </li>
                    <li class="menu">
                      <div data-delay="0" data-hover="false" class="nav-dropdown w-dropdown">
                        <div
                          class="nav-dropdown-toggle-2 w-dropdown-toggle"
                          id="w-dropdown-toggle-7"
                          aria-controls="w-dropdown-list-7"
                          aria-haspopup="menu"
                          aria-expanded="false"
                          role="button"
                          tabindex="0"
                        >
                          <div class="nav-dropdown-icon-2 w-icon-dropdown-toggle" aria-hidden="true"></div>
                          <div class="text-block-31">Doors</div>
                        </div>
                        <nav
                          class="nav-dropdown-list-2 shadow-three mobile-shadow-hide w-dropdown-list"
                          id="w-dropdown-list-7"
                          aria-labelledby="w-dropdown-toggle-7"
                        >
                          <div data-delay="0" data-hover="false" class="nav-dropdown w-dropdown">
                            <div
                              class="nav-dropdown-toggle-2 w-dropdown-toggle"
                              id="w-dropdown-toggle-8"
                              aria-controls="w-dropdown-list-8"
                              aria-haspopup="menu"
                              aria-expanded="false"
                              role="button"
                              tabindex="0"
                            >
                              <div class="nav-dropdown-icon-2 w-icon-dropdown-toggle" aria-hidden="true"></div>
                              <div>Materials</div>
                            </div>
                            <nav
                              class="nav-dropdown-list-2 shadow-three mobile-shadow-hide w-dropdown-list"
                              id="w-dropdown-list-8"
                              aria-labelledby="w-dropdown-toggle-8"
                            >
                              <a href="/doors" class="nav-dropdown-link-5 w-dropdown-link"
                                >All</a
                              ><a
                                href="/doors/vinyl-doors"
                                class="nav-dropdown-link-5 w-dropdown-link"
                                >Vinyl</a
                              ><a
                                href="/doors/wood-clad-doors"
                                class="nav-dropdown-link-5 w-dropdown-link"
                                >Wood clad</a
                              ><a
                                href="/doors/fiberglass-doors"
                                class="nav-dropdown-link-5 w-dropdown-link"
                                >Fiberglass</a
                              ><a
                                href="/doors/wood-doors"
                                class="nav-dropdown-link-5 w-dropdown-link"
                                >Wood</a
                              ><a
                                href="/doors/aluminum-doors"
                                class="nav-dropdown-link-5 w-dropdown-link"
                                >Aluminium</a
                              ><a
                                href="/doors/steel-doors"
                                class="nav-dropdown-link-5 w-dropdown-link"
                                >Steel</a
                              >
                            </nav>
                          </div>
                          <div data-delay="0" data-hover="false" class="nav-dropdown w-dropdown">
                            <div
                              class="nav-dropdown-toggle-2 w-dropdown-toggle"
                              id="w-dropdown-toggle-9"
                              aria-controls="w-dropdown-list-9"
                              aria-haspopup="menu"
                              aria-expanded="false"
                              role="button"
                              tabindex="0"
                            >
                              <div class="nav-dropdown-icon-2 w-icon-dropdown-toggle" aria-hidden="true"></div>
                              <div>Brands</div>
                            </div>
                            <nav
                              class="nav-dropdown-list-2 shadow-three mobile-shadow-hide w-dropdown-list"
                              id="w-dropdown-list-9"
                              aria-labelledby="w-dropdown-toggle-9"
                            >
                              <a href="/brand" class="nav-dropdown-link-5 w-dropdown-link"
                                >All</a
                              ><a
                                href="/brands/marvin"
                                class="nav-dropdown-link-5 w-dropdown-link"
                                >Marvin</a
                              ><a
                                href="/brands/milgard"
                                class="nav-dropdown-link-5 w-dropdown-link"
                                >Milgard</a
                              ><a
                                href="/brands/jeld-wen"
                                class="nav-dropdown-link-5 w-dropdown-link"
                                >Jeld-Wen</a
                              ><a
                                href="/brands/anlin"
                                class="nav-dropdown-link-5 w-dropdown-link"
                                >Anlin</a
                              ><a
                                href="/brands/italwindows"
                                class="nav-dropdown-link-5 w-dropdown-link"
                                >Italwindows</a
                              ><a
                                href="/brands/andersen"
                                class="nav-dropdown-link-5 w-dropdown-link"
                                >Andersen</a
                              ><a
                                href="/brands/ply-gem"
                                class="nav-dropdown-link-5 w-dropdown-link"
                                >Ply Gem</a
                              ><a
                                href="/brands/simonton"
                                class="nav-dropdown-link-5 w-dropdown-link"
                                >Simonton</a
                              ><a
                                href="/brands/alside"
                                class="nav-dropdown-link-5 w-dropdown-link"
                                >Alside</a
                              ><a
                                href="/brands/western-window-systems"
                                class="nav-dropdown-link-5 w-dropdown-link"
                                >Western Window Systems</a
                              ><a
                                href="/brands/all-weather-architectural-aluminum"
                                class="nav-dropdown-link-5 w-dropdown-link"
                                >All Weather Architectural Aluminum</a
                              >
                            </nav>
                          </div>
                        </nav>
                      </div>
                    </li>
                    <li class="menu">
                      <div data-delay="0" data-hover="false" class="nav-dropdown w-dropdown">
                        <div
                          class="nav-dropdown-toggle-2 w-dropdown-toggle"
                          id="w-dropdown-toggle-10"
                          aria-controls="w-dropdown-list-10"
                          aria-haspopup="menu"
                          aria-expanded="false"
                          role="button"
                          tabindex="0"
                        >
                          <div class="nav-dropdown-icon-2 w-icon-dropdown-toggle" aria-hidden="true"></div>
                          <div class="text-block-33">Learning Center</div>
                        </div>
                        <nav
                          class="nav-dropdown-list-2 shadow-three mobile-shadow-hide w-dropdown-list"
                          id="w-dropdown-list-10"
                          aria-labelledby="w-dropdown-toggle-10"
                        >
                          <a
                            href="/blog"
                            class="nav-dropdown-link-5 w-dropdown-link"
                            tabindex="0"
                            >Knowledge Articles</a
                          ><a
                            href="/blog/how-to-measure-windows-for-replacement"
                            class="nav-dropdown-link-5 w-dropdown-link"
                            tabindex="0"
                            >Window Measurement Guide</a
                          ><a
                            href="/blog/comprehensive-guide-to-choosing-the-right-replacement-windows"
                            class="nav-dropdown-link-5 w-dropdown-link"
                            tabindex="0"
                            >Tips for Windows Replacement</a
                          ><a
                            href="/blog/window-buyers-guide"
                            class="nav-dropdown-link-5 w-dropdown-link"
                            tabindex="0"
                            >Window Buyer's Guide</a
                          ><a
                            href="/blog/the-ultimate-door-buyers-guide"
                            class="nav-dropdown-link-5 w-dropdown-link"
                            tabindex="0"
                            >Door Buyer's Guide</a
                          ><a
                            href="/glossary"
                            class="nav-dropdown-link-5 w-dropdown-link"
                            tabindex="0"
                            >Glossary</a
                          ><a
                            href="/faq"
                            class="nav-dropdown-link-5 w-dropdown-link"
                            tabindex="0"
                            >Frequently Asked Questions</a
                          >
                        </nav>
                      </div>
                    </li>
                    <li class="menu">
                      <div data-delay="0" data-hover="false" class="nav-dropdown w-dropdown">
                        <div
                          class="nav-dropdown-toggle-2 w-dropdown-toggle"
                          id="w-dropdown-toggle-11"
                          aria-controls="w-dropdown-list-11"
                          aria-haspopup="menu"
                          aria-expanded="false"
                          role="button"
                          tabindex="0"
                        >
                          <div class="nav-dropdown-icon-2 w-icon-dropdown-toggle" aria-hidden="true"></div>
                          <div class="text-block-34">Resources &amp; Support</div>
                        </div>
                        <nav
                          class="nav-dropdown-list-2 shadow-three mobile-shadow-hide w-dropdown-list"
                          id="w-dropdown-list-11"
                          aria-labelledby="w-dropdown-toggle-11"
                        >
                          <a
                            href="/special-offers"
                            class="nav-dropdown-link-5 w-dropdown-link"
                            tabindex="0"
                            >Special Offers</a
                          ><a
                            href="/financing"
                            class="nav-dropdown-link-5 w-dropdown-link"
                            tabindex="0"
                            >Financing</a
                          ><a
                            href="/gallery"
                            class="nav-dropdown-link-5 w-dropdown-link"
                            tabindex="0"
                            >Gallery</a
                          ><a
                            href="/about"
                            class="nav-dropdown-link-5 w-dropdown-link"
                            tabindex="0"
                            >About Us</a
                          ><a
                            href="/contacts"
                            class="nav-dropdown-link-5 w-dropdown-link"
                            tabindex="0"
                            >Contact Us</a
                          ><a
                            href="/testimonials"
                            class="nav-dropdown-link-5 w-dropdown-link"
                            tabindex="0"
                            >Testimonials</a
                          >
                        </nav>
                      </div>
                    </li>
                  </ul>
                </nav>
                <div
                  class="menu-button w-nav-button"
                  style="-webkit-user-select: text"
                  aria-label="menu"
                  role="button"
                  tabindex="0"
                  aria-controls="w-nav-overlay-2"
                  aria-haspopup="menu"
                  aria-expanded="false"
                >
                  <div class="icon w-icon-nav-menu"></div>
                </div>
              </div>
            </div>
            <div class="w-nav-overlay" data-wf-ignore="" id="w-nav-overlay-2"></div>
          </div>
        </div>
      </div>

      <a href="#" class="mobile-fab-estimate" data-open-estimate-modal aria-label="Request a Free Estimate">
          <svg class="mobile-fab-estimate__icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 14H7v-2h5v2zm5-4H7v-2h10v2zm0-4H7V7h10v2z"/>
          </svg>
          <span class="mobile-fab-estimate__label">Free Estimate</span>
        </a>

        <div class="mobile-estimate-modal" id="mobileEstimateModal" aria-hidden="true">
          <div class="mobile-estimate-modal__backdrop" data-close-estimate-modal></div>
          <div class="mobile-estimate-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="mobileEstimateTitle">
            <button type="button" class="mobile-estimate-modal__close" data-close-estimate-modal aria-label="Close form">×</button>
            <h3 id="mobileEstimateTitle" class="mobile-estimate-modal__title">Request a Free Estimate</h3>
            <div class="mobile-estimate-modal__promo promo-offer-context--modal w-richtext" data-estimate-modal-promo>
              {!! promotion_home_html() !!}
            </div>
            <div class="mobile-estimate-modal__form-wrap w-form">
              <form id="wf-form-Mobile-Estimate-Modal" name="wf-form-Mobile-Estimate-Modal" method="get" class="mobile-estimate-modal__form js-laravel-lead-form">
                <input type="text" name="Name" placeholder="Full name*" required class="w-input" />
                <input type="email" name="Email" placeholder="Email*" required class="w-input" />
                <input type="tel" name="Phone" placeholder="{{ site_phone_display() }}" required class="w-input" />
                <input type="text" name="Subject" placeholder="City" class="w-input" />
                <textarea name="Message" maxlength="5000" placeholder="Tell us about your project" class="w-input"></textarea>
                <div class="primary-button space-between-v1">
                  <input
                    type="submit"
                    data-wait="Please wait..."
                    class="inside-input-button text-light w-button"
                    value="Send Request"
                  />
                </div>
              </form>
              <div class="w-form-done" tabindex="-1" role="region" aria-label="Mobile Estimate Form success">
                <div>Thank you! Your submission has been received!</div>
              </div>
              <div class="w-form-fail" tabindex="-1" role="region" aria-label="Mobile Estimate Form failure">
                <div>Oops! Something went wrong while submitting the form.</div>
              </div>
            </div>
          </div>
        </div>

        @once
          @php
            $siteCustomJsPath = public_path('webflow-overrides/site-custom.js');
            $siteCustomJsVersion = file_exists($siteCustomJsPath) ? (string) filemtime($siteCustomJsPath) : '1';
          @endphp
          <script src="/webflow-overrides/site-custom.js?v={{ $siteCustomJsVersion }}"></script>
        @endonce
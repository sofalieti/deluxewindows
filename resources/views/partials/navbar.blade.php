      @once
        <style>
          .mobile-top-strip {
            display: none;
          }

          .mobile-estimate-btn {
            display: none;
          }

          @media (min-width: 992px) {
            /* Desktop dropdown width should follow content */
            .header-wrapper-2 .dropdown-wrapper.dropdown-default {
              position: relative;
            }

            .header-wrapper-2 .dropdown-list-2.dropdown-v1.w-dropdown-list {
              left: 0 !important;
              right: auto !important;
              width: max-content !important;
              min-width: 520px;
              max-width: min(94vw, 1120px) !important;
            }

            .header-wrapper-2 .dropdown-pd-2.dropdown-v4 {
              width: auto !important;
              max-width: 100% !important;
            }

            .header-wrapper-2 .w-layout-grid.grid-2-columns-2.dropdown-link-column.v4 {
              width: max-content !important;
              max-width: 100% !important;
            }
          }

          @media (max-width: 991px) {
            .header-container-2 > .navbar.w-nav,
            .header-container-2 > .header-wrapper-2.w-nav {
              display: none !important;
            }

            .mobile-top-strip {
              display: block;
              background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
              border-bottom: 1px solid #e7edf5;
            }

            .mobile-top-strip__inner {
              position: relative;
              min-height: 38px;
              padding: 8px 14px;
              display: flex;
              align-items: center;
              justify-content: space-between;
              gap: 10px;
            }

            .mobile-top-strip__text {
              color: #334155;
              font-size: 12px;
              line-height: 1.2;
              text-align: left;
              letter-spacing: 0.01em;
              display: block;
              flex: 1 1 auto;
              min-width: 0;
              padding: 0;
              order: 1;
            }

            .mobile-top-strip__phone {
              display: inline-flex;
              position: static;
              transform: none;
              align-items: center;
              justify-content: center;
              width: 28px;
              height: 28px;
              border-radius: 999px;
              border: 1px solid #d9e3ee;
              background: #ffffff;
              color: #0f172a;
              text-decoration: none;
              flex: 0 0 auto;
              order: 2;
            }

            .mobile-top-strip__phone-icon {
              width: 14px;
              height: 14px;
              object-fit: contain;
              flex: 0 0 auto;
            }

            .mobile-top-strip__phone span {
              display: none;
            }

            .navbar-3 {
              position: sticky !important;
              top: 0 !important;
              z-index: 1200 !important;
              background: #ffffff;
              border-bottom: 1px solid #e6eaf0;
              box-shadow: 0 8px 22px rgba(15, 23, 42, 0.08);
              transform: none !important;
            }

            .navbar-3 .navbar-container {
              background: #fff;
              position: relative;
              z-index: 1301;
            }

            .navbar-3 .container-regular {
              width: 100%;
              max-width: none;
              padding-left: 14px;
              padding-right: 14px;
            }

            .navbar-3 .navbar-wrapper {
              min-height: 60px;
              display: grid;
              grid-template-columns: minmax(0, 1fr) auto auto;
              grid-template-areas: "logo cta menu";
              align-items: center;
              gap: 8px;
            }

            .navbar-3 .navbar-brand {
              grid-area: logo;
              min-width: 0;
            }

            .navbar-3 .image-24 {
              display: block;
              width: auto;
              max-width: 124px;
              height: auto;
            }

            .navbar-3 .link-15 {
              display: none !important;
            }

            .navbar-3 .link-15 strong {
              display: none;
            }

            .navbar-3 .link-15__icon {
              width: 14px;
              height: 14px;
              object-fit: contain;
              flex: 0 0 auto;
            }

            .navbar-3 .link-15__label {
              min-width: 0;
            }

            .navbar-3 .menu-button {
              grid-area: menu;
              justify-self: end;
              position: relative;
              z-index: 1302;
              width: 44px;
              height: 44px;
              margin: 0 !important;
              padding: 0 !important;
              display: inline-flex;
              align-items: center;
              justify-content: center;
              background: transparent !important;
              color: inherit !important;
              border: 0;
              transform: none !important;
              left: auto !important;
              right: auto !important;
            }

            .navbar-3 .menu-button.w--open {
              margin: 0 !important;
              padding: 0 !important;
              background: transparent !important;
              color: inherit !important;
              transform: none !important;
              left: auto !important;
              right: auto !important;
            }

            .navbar-3 .menu-button .icon {
              color: #0f172a !important;
              transform: none !important;
            }

            .navbar-3 .mobile-estimate-btn {
              grid-area: cta;
              display: inline-flex;
              align-items: center;
              justify-content: center;
              min-height: 36px;
              min-width: 168px;
              padding: 0 12px;
              white-space: nowrap;
            }

            .navbar-3 .mobile-estimate-btn > div {
              font-size: 10px;
              line-height: 1.05;
              font-weight: 700;
              letter-spacing: 0.01em;
              text-align: center;
              white-space: nowrap;
            }

            .navbar-3 .mobile-estimate-btn:active {
              transform: translateY(1px);
            }

            .navbar-3 .w-nav-overlay {
              z-index: 1300 !important;
              background: rgba(15, 23, 42, 0.62);
              backdrop-filter: blur(3px);
              -webkit-backdrop-filter: blur(3px);
            }

            .navbar-3 .nav-menu-wrapper-4.w-nav-menu {
              width: 100%;
              max-width: 100%;
              height: 100dvh;
              padding: 86px 16px 24px;
              background: #ffffff;
              overflow-y: auto;
              -webkit-overflow-scrolling: touch;
            }

            .navbar-3 .nav-menu-2 {
              display: flex;
              flex-direction: column;
              gap: 12px;
            }

            .navbar-3 .nav-button-wrapper .primary-button-2 {
              width: 100%;
              justify-content: center;
            }
          }

          @media (max-width: 479px) {
            .mobile-top-strip__inner {
              padding-left: max(12px, env(safe-area-inset-left));
              padding-right: max(12px, env(safe-area-inset-right));
            }

            .navbar-3 .container-regular {
              padding-left: max(12px, env(safe-area-inset-left));
              padding-right: max(12px, env(safe-area-inset-right));
            }

            .navbar-3 .image-24 {
              max-width: 112px;
            }

            .navbar-3 .mobile-estimate-btn {
              min-height: 32px;
              min-width: 150px;
              padding: 0 10px;
            }

            .navbar-3 .mobile-estimate-btn > div {
              font-size: 9px;
            }
          }

          @media (max-width: 390px) {
            .navbar-3 .image-24 {
              max-width: 104px;
            }
          }

        </style>
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
                <a href="tel:{{ site_phone_tel() }}" class="link-block phone w-inline-block"
                  ><img
                    width="20"
                    loading="lazy"
                    alt=""
                    src="/webflow-assets/images/6841ddf8ace3d9d9facb1950_phone-icon-property-x-webflow-template.svg"
                    class="image-38"
                /></a>
                <div class="link-block odsf">
                  <img
                    width="20"
                    loading="lazy"
                    alt=""
                    src="/webflow-assets/images/687e6d1e97ec845884f78baa_sell_24dp_FFFFFF_FILL1_wght400_GRAD0_opsz24%201.svg"
                  />
                  <div class="text-block-17">
                    <a href="/special-offers" class="link-10">Special Offers</a>
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
        <div class="navbar-3">
          <div class="mobile-top-strip" aria-label="Company info">
            <div class="mobile-top-strip__inner">
              <a href="tel:{{ site_phone_tel() }}" class="mobile-top-strip__phone">
                <img
                  src="/webflow-assets/images/6841ddf8ace3d9d9facb1950_phone-icon-property-x-webflow-template.svg"
                  alt=""
                  loading="lazy"
                  class="mobile-top-strip__phone-icon"
                />
                <span>{{ site_phone_display() }}</span>
              </a>
              <span class="mobile-top-strip__text">We are – 100% employee owned &amp; over 30 years in business!</span>
            </div>
          </div>
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
                ><a href="tel:{{ site_phone_tel() }}" class="link-15"
                  ><img
                    src="/webflow-assets/images/687559a123cece2e95a41a6f_phone_enabled_24dp_FFFFFF_FILL1_wght400_GRAD0_opsz24.svg"
                    alt=""
                    loading="lazy"
                    class="link-15__icon"
                  /><span class="link-15__label">{{ site_phone_display() }}</span></a
                >
                <a href="#" class="primary-button-2 mobile-estimate-btn w-inline-block" data-open-estimate-modal><div>Request a Free Estimate</div></a>
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
                    <li class="menu">
                      <div class="nav-button-wrapper">
                        <a href="/contacts" class="primary-button-2 w-inline-block"
                          ><div class="text-block-35">Get Quote</div></a
                        >
                      </div>
                    </li>
                  </ul>
                  <a href="tel:{{ site_phone_tel() }}">&nbsp;{{ site_phone_display() }}</a>
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

        <div class="mobile-estimate-modal" id="mobileEstimateModal" aria-hidden="true">
          <div class="mobile-estimate-modal__backdrop" data-close-estimate-modal></div>
          <div class="mobile-estimate-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="mobileEstimateTitle">
            <button type="button" class="mobile-estimate-modal__close" data-close-estimate-modal aria-label="Close form">×</button>
            <h3 id="mobileEstimateTitle" class="mobile-estimate-modal__title">Request a Free Estimate</h3>
            <div class="mobile-estimate-modal__promo">
              <div class="mobile-estimate-modal__promo-kicker">Limited-Time Deal</div>
              <div class="mobile-estimate-modal__promo-main">{{ promotion_percent_label() }}</div>
              <div class="mobile-estimate-modal__promo-sub">{{ promotion_name() }} • Ends {{ promotion_date('us-short') }}</div>
            </div>
            <div class="mobile-estimate-modal__form-wrap w-form">
              <form id="wf-form-Mobile-Estimate-Modal" name="wf-form-Mobile-Estimate-Modal" method="get" class="mobile-estimate-modal__form js-laravel-lead-form">
                <input type="text" name="Name" placeholder="Full name*" required class="w-input" />
                <input type="email" name="Email" placeholder="Email*" required class="w-input" />
                <input type="tel" name="Phone" placeholder="{{ site_phone_display() }}" required class="w-input" />
                <input type="text" name="Subject" placeholder="City" class="w-input" />
                <textarea name="Message" maxlength="5000" placeholder="Tell us about your project" class="w-input"></textarea>
                <input type="submit" value="Send Request" class="w-button" />
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
          <style>
            .mobile-estimate-modal {
              position: fixed;
              inset: 0;
              z-index: 2400;
              display: none;
            }

            .mobile-estimate-modal.is-open {
              display: block;
            }

            .mobile-estimate-modal__backdrop {
              position: absolute;
              inset: 0;
              background: rgba(15, 23, 42, 0.6);
              backdrop-filter: blur(2px);
              -webkit-backdrop-filter: blur(2px);
            }

            .mobile-estimate-modal__dialog {
              position: absolute;
              left: 50%;
              top: 50%;
              transform: translate(-50%, -50%);
              width: min(92vw, 520px);
              max-height: min(86vh, 760px);
              overflow: auto;
              background: #fff;
              border-radius: 14px;
              box-shadow: 0 22px 55px rgba(15, 23, 42, 0.3);
              padding: 20px 16px 16px;
            }

            .mobile-estimate-modal__close {
              position: absolute;
              right: 10px;
              top: 8px;
              border: 0;
              background: transparent;
              font-size: 28px;
              line-height: 1;
              color: #334155;
              cursor: pointer;
            }

            .mobile-estimate-modal__title {
              margin: 0 28px 8px 0;
              font-size: 20px;
              line-height: 1.2;
              color: #0f172a;
            }

            .mobile-estimate-modal__promo {
              margin-bottom: 12px;
              border: 1px solid #f7b553;
              background: linear-gradient(135deg, #ff8a00 0%, #ffb347 100%);
              border-radius: 10px;
              padding: 10px 12px;
              color: #ffffff;
              box-shadow: 0 8px 18px rgba(255, 138, 0, 0.28);
            }

            .mobile-estimate-modal__promo-kicker {
              font-size: 11px;
              line-height: 1.2;
              opacity: 0.95;
              letter-spacing: 0.04em;
              text-transform: uppercase;
              font-weight: 700;
            }

            .mobile-estimate-modal__promo-main {
              margin-top: 2px;
              font-size: 24px;
              line-height: 1.05;
              font-weight: 800;
            }

            .mobile-estimate-modal__promo-sub {
              margin-top: 2px;
              font-size: 12px;
              line-height: 1.25;
              font-weight: 600;
            }

            .mobile-estimate-modal__form .w-input {
              margin-bottom: 8px;
              min-height: 42px;
              border-radius: 9px;
              border-color: #dbe5ef;
            }

            .mobile-estimate-modal__form textarea.w-input {
              min-height: 88px;
              resize: vertical;
            }

            .mobile-estimate-modal__form .w-button {
              width: 100%;
              margin-top: 2px;
              min-height: 44px;
              border-radius: 999px;
              border: 1px solid #ef8a00;
              background: linear-gradient(180deg, #f7a71a 0%, #ef8a00 100%);
              color: #ffffff;
              font-weight: 800;
              letter-spacing: 0.01em;
              box-shadow: 0 8px 16px rgba(239, 138, 0, 0.28);
            }
          </style>
        @endonce
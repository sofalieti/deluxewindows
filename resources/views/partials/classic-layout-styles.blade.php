    @php
      $promoOfferCssPath = public_path('webflow-assets/css/promo-offer.css');
      $promoOfferCssVersion = file_exists($promoOfferCssPath) ? (string) filemtime($promoOfferCssPath) : '1';
    @endphp
    <link href="/webflow-assets/css/promo-offer.css?v={{ $promoOfferCssVersion }}" rel="stylesheet" type="text/css" />
    <style>
      .w-webflow-badge { display: none !important; }

      /* Same layout spacing fixes as home.blade.php */
      .div-block-59 { margin-bottom: 0 !important; }
      .div-block-59 > .w-layout-blockcontainer.container-default { padding-bottom: 0 !important; }
      .section.top-none { margin-top: 0 !important; }

      /* Navbar/footer fade-in runs only on home bundle — keep visible elsewhere */
      .header-container-wrapper-2 { opacity: 1 !important; }
      .footer-wrapper,
      .footer-wrapper [data-w-id],
      .header-container-wrapper-2 [data-w-id] {
        opacity: 1 !important;
        transform: none !important;
        transition: none !important;
        animation: none !important;
      }

      .w-nav-overlay,
      .w-nav-menu {
        transition: none !important;
      }

      .primary-button-2 {
        border-radius: 10px !important;
      }

      /* Hard overrides to avoid Webflow state drift */
      @media (min-width: 992px) {
        .header-wrapper-2 .dropdown-toogle-2.w-dropdown-toggle {
          display: inline-flex !important;
          align-items: center !important;
          column-gap: 6px !important;
        }
        .header-wrapper-2 .dropdown-toogle-2.w-dropdown-toggle > div:first-child {
          display: inline-flex !important;
          align-items: center !important;
          line-height: 1.1 !important;
        }
      }

      @media (max-width: 991px) {
        .navbar-3 .menu-button.w-nav-button,
        .navbar-3 .menu-button.w-nav-button.w--open {
          width: 44px !important;
          height: 44px !important;
          margin: 0 !important;
          padding: 0 !important;
          transform: none !important;
          left: auto !important;
          right: auto !important;
          background: transparent !important;
          color: inherit !important;
        }

        .navbar-3 .menu-button.w-nav-button .icon {
          color: #0f172a !important;
          transform: none !important;
        }
      }

      /* Blog pages: no hero form block */
      .blog-page .div-block-59 { display: none !important; }

    </style>
    @include('partials.google-tags')

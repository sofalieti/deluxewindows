    <link href="/webflow-assets/css/promo-offer.css" rel="stylesheet" type="text/css" />
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

      /* Blog pages: no hero form block */
      .blog-page .div-block-59 { display: none !important; }

    </style>
    @include('partials.google-tags')

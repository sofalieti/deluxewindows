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

      /* Discount info: red store-like promo badge card */
      .promo-offer-card {
        position: relative;
        border-radius: 16px;
        padding: 14px 14px 12px;
        background: linear-gradient(145deg, #fff6f6 0%, #ffe9e9 100%);
        border: 1px solid #ffcfcf;
        box-shadow: 0 8px 20px rgba(165, 32, 32, 0.14);
      }
      .promo-offer-badge {
        display: inline-block;
        margin-bottom: 8px;
        padding: 4px 10px;
        border-radius: 999px;
        background: #d62020;
        color: #fff;
        font-weight: 700;
        font-size: 12px;
        letter-spacing: .4px;
      }
      .promo-offer-title {
        margin: 0 0 10px 0;
        font-size: 17px;
        line-height: 1.35;
        color: #3a1212;
      }
      .promo-offer-price-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 6px;
      }
      .promo-offer-price-row--highlight {
        margin-bottom: 4px;
      }
      .promo-offer-label {
        font-size: 12px;
        color: #7b3a3a;
        text-transform: uppercase;
        letter-spacing: .4px;
      }
      .promo-offer-old-price {
        color: #7b3a3a;
        font-weight: 600;
      }
      .promo-offer-new-price {
        color: #b21111;
        font-size: 24px;
        font-weight: 800;
        line-height: 1;
      }
      .promo-offer-note {
        font-size: 12px;
        color: #7b3a3a;
      }
    </style>
    @include('partials.google-tags')

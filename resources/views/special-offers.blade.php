<!DOCTYPE html>
<html
  data-wf-domain="www.deluxewindows.com"
  data-wf-page="special-offers"
  data-wf-site="6841ddf8ace3d9d9facb14fd"
  lang="en"
  class="w-mod-js w-mod-ix"
>
  <head>
    <meta charset="utf-8" />
    <link href="https://cdn.prod.website-files.com" rel="preconnect" crossorigin="anonymous" />
    <title>Special Offers &amp; Coupons | Deluxe Windows – Bay Area</title>
    <meta content="Save big on window and door replacement with Deluxe Windows. Current Bay Area deals include 40% off windows and doors, senior discounts, and military discounts." name="description" />
    <meta content="Special Offers &amp; Coupons | Deluxe Windows – Bay Area" property="og:title" />
    <meta content="Save big on window and door replacement with Deluxe Windows. Current Bay Area deals include 40% off windows and doors, senior discounts, and military discounts." property="og:description" />
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
      /* ── Offer Cards ── */
      .offers-list {
        display: flex;
        flex-direction: column;
        gap: 32px;
      }
      .offer-card {
        background: #fff;
        border: 1px solid #d8e3ef;
        border-radius: 20px;
        box-shadow: 0 10px 28px rgba(13,37,62,0.08);
        overflow: hidden;
        display: grid;
        grid-template-columns: 1fr 1fr;
      }
      .offer-card.reverse {
        direction: rtl;
      }
      .offer-card.reverse > * {
        direction: ltr;
      }
      .offer-card-image {
        aspect-ratio: 4/3;
        overflow: hidden;
        background: #e8edf2;
      }
      .offer-card-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
        transition: transform 0.4s ease;
      }
      .offer-card:hover .offer-card-image img {
        transform: scale(1.03);
      }
      .offer-card-body {
        padding: 44px 40px;
        display: flex;
        flex-direction: column;
        justify-content: center;
      }
      .offer-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: #fff3dc;
        border: 1px solid #f5d48a;
        border-radius: 30px;
        padding: 5px 14px;
        font-size: 0.78rem;
        font-weight: 700;
        color: #b57a0a;
        text-transform: uppercase;
        letter-spacing: 0.07em;
        margin-bottom: 18px;
        width: fit-content;
      }
      .offer-badge.expires { background: #fef2f2; border-color: #fca5a5; color: #b91c1c; }
      .offer-badge.active  { background: #ecfdf5; border-color: #6ee7b7; color: #065f46; }
      .offer-discount {
        font-size: 3rem;
        font-weight: 900;
        color: #0f4d89;
        letter-spacing: -0.04em;
        line-height: 1;
        margin-bottom: 10px;
      }
      .offer-discount span {
        font-size: 1.6rem;
        font-weight: 700;
        vertical-align: middle;
      }
      .offer-title {
        font-size: 1.5rem;
        font-weight: 800;
        color: #15293f;
        margin-bottom: 14px;
        letter-spacing: -0.01em;
      }
      .offer-description {
        font-size: 0.93rem;
        color: #5a6a7c;
        line-height: 1.65;
        margin-bottom: 28px;
      }
      .offer-cta-btn {
        display: inline-block;
        background: linear-gradient(180deg, #0f4d89, #0a3b69);
        color: #fff;
        padding: 13px 26px;
        border-radius: 12px;
        font-weight: 700;
        font-size: 0.92rem;
        text-decoration: none;
        width: fit-content;
        box-shadow: 0 6px 16px rgba(15,77,137,0.28);
        transition: transform 0.18s, filter 0.18s;
      }
      .offer-cta-btn:hover { transform: translateY(-1px); filter: brightness(1.06); }

      /* ── Schedule Form Section ── */
      .offers-schedule-wrap {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 60px;
        align-items: start;
      }
      .offers-schedule-title {
        font-size: 2rem;
        font-weight: 800;
        color: #15293f;
        letter-spacing: -0.02em;
        margin-bottom: 14px;
      }
      .offers-schedule-sub {
        font-size: 0.97rem;
        color: #5a6a7c;
        line-height: 1.65;
        margin-bottom: 24px;
      }
      .offers-phone-link {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        font-size: 1.55rem;
        font-weight: 800;
        color: #0f4d89;
        text-decoration: none;
        letter-spacing: -0.02em;
        transition: color 0.2s;
      }
      .offers-phone-link:hover { color: #0a3b69; }
      .offers-phone-icon {
        width: 40px; height: 40px;
        border-radius: 50%;
        background: linear-gradient(135deg, #0f4d89, #1a6abf);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
      }
      .offers-eligibility-note {
        margin-top: 24px;
        padding: 16px 18px;
        background: #f4f8fc;
        border: 1px solid #d8e3ef;
        border-radius: 12px;
        font-size: 0.85rem;
        color: #5a6a7c;
        line-height: 1.6;
      }

      /* ── Responsive ── */
      @media (max-width: 991px) {
        .offer-card { grid-template-columns: 1fr; }
        .offer-card.reverse { direction: ltr; }
        .offer-card-image { aspect-ratio: 16/9; }
        .offer-card-body { padding: 28px 24px; }
        .offer-discount { font-size: 2.4rem; }
        .offers-schedule-wrap { grid-template-columns: 1fr; gap: 40px; }
      }
    </style>

    <script>
      (function () {
        let gtagLoaded = false;
        function loadGtag() {
          if (gtagLoaded) return; gtagLoaded = true;
          const s = document.createElement("script");
          s.src = "https://www.googletagmanager.com/gtag/js?id=G-JHYBB0THJM"; s.async = true;
          document.head.appendChild(s);
          window.dataLayer = window.dataLayer || [];
          function gtag() { dataLayer.push(arguments); } window.gtag = gtag;
          gtag("js", new Date()); gtag("config", "G-JHYBB0THJM"); gtag("config", "AW-1030787786");
        }
        window.addEventListener("scroll", loadGtag, { once: true });
        window.addEventListener("click",  loadGtag, { once: true });
        setTimeout(loadGtag, 3000);
      })();
    </script>
    <script>window.$zoho = window.$zoho || {}; $zoho.salesiq = $zoho.salesiq || { ready: function () {} };</script>
    <link href="https://core.service.elfsight.com/" rel="preconnect" crossorigin="" />
  </head>

  <body class="body-18 height-auto offers-page">
    <div class="page-wrapper">

      @include('partials.navbar')
      @include('partials.header-scripts')

      {{-- Breadcrumb --}}
      <section class="section_breadcrumbs section-121">
        <div class="w-layout-blockcontainer container-default breadcrumbs-container w-container">
          <div class="breadcrumbs-wrapper">
            <a href="/" class="breadcrumb-link">Home</a>
            <div class="breadcrumb-div">/</div>
            <div class="breadcrumb-text">Special Offers</div>
          </div>
        </div>
      </section>

      {{-- Page Header --}}
      <section class="section pd-top-80px top-none">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="mg-top-extra-large">
            <div class="title-left---content-right">
              <h1 class="display-9 mid">Special Offers</h1>
            </div>
            <div class="mg-top-small">
              <p class="paragraph-20">
                Limited-time window &amp; door replacement offers for Bay Area homeowners. Act fast — these deals won't last.
              </p>
            </div>
          </div>
        </div>
      </section>

      {{-- Offer Cards --}}
      <section class="section" style="padding-top:24px;padding-bottom:80px;">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="offers-list">

            {{-- Offer 1: 40% OFF Windows --}}
            <div class="offer-card">
              <div class="offer-card-image">
                <img src="https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb1586/688236fed9cc28b78fb10d04_IMG_2963%201.avif"
                  alt="40% Off Windows" loading="lazy" />
              </div>
              <div class="offer-card-body">
                <div class="offer-badge expires">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                  Expires November 30, 2025
                </div>
                <div class="offer-discount">40<span>% OFF</span></div>
                <div class="offer-title">Windows</div>
                <p class="offer-description">
                  30% national window discount requires a minimum purchase of 3 Comfort 365 Windows®. Earn up to an additional 10% off with YES! Program participation (up to $2,000 in savings).
                </p>
                <a href="#schedule" class="offer-cta-btn">Schedule Your Coupon</a>
              </div>
            </div>

            {{-- Offer 2: 40% OFF Doors --}}
            <div class="offer-card reverse">
              <div class="offer-card-image">
                <img src="https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb14fd/688129a7fdac73f612686f21_pexels-linkedin-1251842.avif"
                  alt="40% Off Doors" loading="lazy" />
              </div>
              <div class="offer-card-body">
                <div class="offer-badge expires">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                  Expires November 30, 2025
                </div>
                <div class="offer-discount">40<span>% OFF</span></div>
                <div class="offer-title">Doors</div>
                <p class="offer-description">
                  30% national door discount requires a minimum purchase of 3 qualifying units. Earn up to an additional 10% off with YES! Program participation (up to $2,000 in savings).
                </p>
                <a href="#schedule" class="offer-cta-btn">Schedule Your Coupon</a>
              </div>
            </div>

            {{-- Offer 3: Senior Citizen Discount --}}
            <div class="offer-card">
              <div class="offer-card-image">
                <img src="https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb14fd/688238f1c9cdbcae6e14caea_a-c-cmYXzX-r3mY-unsplash.avif"
                  alt="Senior Citizen Discount" loading="lazy" />
              </div>
              <div class="offer-card-body">
                <div class="offer-badge active">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><path d="M22 4L12 14.01l-3-3"/></svg>
                  Available Now
                </div>
                <div class="offer-discount">5<span>% OFF</span></div>
                <div class="offer-title">Senior Citizen Discount</div>
                <p class="offer-description">
                  An additional 5% off your entire project for homeowners aged 55 and over. Valid state photo ID required at time of estimate. Cannot be combined with other percentage-off discounts.
                </p>
                <a href="#schedule" class="offer-cta-btn">Schedule Your Coupon</a>
              </div>
            </div>

            {{-- Offer 4: Military Discount --}}
            <div class="offer-card reverse">
              <div class="offer-card-image">
                <img src="https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb14fd/6882394edeeffd727ef123a8_aaron-burden-9C8r4QUwZRQ-unsplash.avif"
                  alt="Military Discount" loading="lazy" />
              </div>
              <div class="offer-card-body">
                <div class="offer-badge expires">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                  Expires December 31, 2025
                </div>
                <div class="offer-discount" style="font-size:2.2rem;">Thank You<span style="font-size:1rem;display:block;margin-top:4px;">for Your Service</span></div>
                <div class="offer-title">Military Discount</div>
                <p class="offer-description">
                  Exclusive discount for active duty military, reserves, and veterans within 2 years of separation. Verification required. Our way of saying thank you for your service to our country.
                </p>
                <a href="#schedule" class="offer-cta-btn">Schedule Your Coupon</a>
              </div>
            </div>

          </div>
        </div>
      </section>

      {{-- Schedule Form --}}
      <section class="section" id="schedule" style="padding-top:0;padding-bottom:100px;background:#f4f8fc;">
        <div class="w-layout-blockcontainer container-default w-container" style="padding-top:64px;">
          <div class="offers-schedule-wrap">
            <div>
              <div class="offers-schedule-title">Schedule Your Coupon</div>
              <p class="offers-schedule-sub">
                Ready to save? Fill out the form and our team will contact you to apply your discount and schedule your free in-home estimate.
              </p>
              <a href="tel:+16504614446" class="offers-phone-link">
                <span class="offers-phone-icon">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81 19.79 19.79 0 01.01 1.18 2 2 0 012 0h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L6.09 7.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 14.92z"/></svg>
                </span>
                (650) 461-4446
              </a>
              <div class="offers-eligibility-note">
                <strong>Offer eligibility:</strong> Offers are available to Bay Area homeowners. Discounts cannot be combined unless stated otherwise. Each offer has specific eligibility requirements — ask our team for details during your consultation.
              </div>
            </div>
            <div>
              @include('partials.contact-form')
            </div>
          </div>
        </div>
      </section>

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

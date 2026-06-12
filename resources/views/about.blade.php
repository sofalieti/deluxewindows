<!DOCTYPE html>
<html
  data-wf-domain="www.deluxewindows.com"
  data-wf-page="about"
  data-wf-site="6841ddf8ace3d9d9facb14fd"
  lang="en"
  class="w-mod-js w-mod-ix"
>
  <head>
    <meta charset="utf-8" />
    <link href="https://cdn.prod.website-files.com" rel="preconnect" crossorigin="anonymous" />
    <title>About Us | Deluxe Windows – Bay Area Door & Window Experts</title>
    <meta content="Learn about Deluxe Windows — Bay Area's trusted door and window replacement experts with 30+ years of experience and 5,000+ completed projects." name="description" />
    <meta content="About Us | Deluxe Windows – Bay Area Door & Window Experts" property="og:title" />
    <meta content="Learn about Deluxe Windows — Bay Area's trusted door and window replacement experts with 30+ years of experience and 5,000+ completed projects." property="og:description" />
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
      /* ── About Hero ── */
      .about-hero {
        background: linear-gradient(135deg, #0f4d89 0%, #0a3b69 100%);
        padding: 80px 0 70px;
        position: relative;
        overflow: hidden;
      }
      .about-hero::before {
        content: '';
        position: absolute;
        inset: 0;
        background: url('https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb14fd/68459655fb9f90434a93ada6_Mask%20group5.avif') center/cover no-repeat;
        opacity: 0.12;
      }
      .about-hero-inner {
        position: relative;
        z-index: 1;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 60px;
        align-items: center;
      }
      .about-hero-title {
        font-size: 2.8rem;
        font-weight: 800;
        line-height: 1.15;
        letter-spacing: -0.03em;
        color: #fff;
        margin: 0 0 20px;
      }
      .about-hero-sub {
        font-size: 1.05rem;
        color: rgba(255,255,255,0.82);
        line-height: 1.65;
        margin: 0 0 32px;
        max-width: 480px;
      }
      .about-hero-actions {
        display: flex;
        gap: 14px;
        flex-wrap: wrap;
        align-items: center;
      }
      .about-hero-btn-primary {
        display: inline-block;
        background: #e79a1f;
        color: #fff;
        padding: 14px 30px;
        border-radius: 12px;
        font-weight: 700;
        font-size: 0.95rem;
        text-decoration: none;
        transition: filter 0.2s, transform 0.2s;
        box-shadow: 0 6px 16px rgba(231,154,31,0.35);
      }
      .about-hero-btn-primary:hover { filter: brightness(1.08); transform: translateY(-1px); }
      .about-hero-btn-secondary {
        display: inline-block;
        color: rgba(255,255,255,0.9);
        font-weight: 600;
        font-size: 0.95rem;
        text-decoration: none;
        padding: 14px 4px;
        border-bottom: 2px solid rgba(255,255,255,0.4);
        transition: border-color 0.2s, color 0.2s;
      }
      .about-hero-btn-secondary:hover { color: #fff; border-color: #fff; }
      .about-hero-image-wrap {
        display: flex;
        justify-content: center;
      }
      .about-hero-image-wrap img {
        width: 100%;
        max-width: 480px;
        border-radius: 20px;
        box-shadow: 0 24px 56px rgba(0,0,0,0.28);
        object-fit: cover;
        aspect-ratio: 4/3;
      }

      /* ── Stats Bar ── */
      .about-stats-bar {
        background: #fff;
        box-shadow: 0 4px 24px rgba(15,42,72,0.09);
      }
      .about-stats-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0;
      }
      .about-stat-item {
        text-align: center;
        padding: 44px 24px;
        border-right: 1px solid #e8f0f8;
      }
      .about-stat-item:last-child { border-right: none; }
      .about-stat-number {
        font-size: 3rem;
        font-weight: 800;
        color: #0f4d89;
        letter-spacing: -0.03em;
        line-height: 1;
        display: block;
      }
      .about-stat-plus { color: #e79a1f; }
      .about-stat-label {
        font-size: 0.95rem;
        color: #5a6a7c;
        margin-top: 8px;
        font-weight: 500;
      }

      /* ── Values Section ── */
      .about-values-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 24px;
      }
      .about-value-card {
        background: #fff;
        border: 1px solid #d8e3ef;
        border-radius: 16px;
        box-shadow: 0 8px 18px rgba(13,37,62,0.07);
        padding: 32px 26px;
        transition: transform 0.22s ease, box-shadow 0.22s ease;
      }
      .about-value-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 16px 32px rgba(13,37,62,0.12);
      }
      .about-value-icon {
        width: 52px;
        height: 52px;
        border-radius: 13px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 20px;
        font-size: 1.4rem;
      }
      .about-value-icon.blue  { background: linear-gradient(135deg, #0f4d89, #1a6abf); }
      .about-value-icon.gold  { background: linear-gradient(135deg, #e79a1f, #c87d14); }
      .about-value-icon.green { background: linear-gradient(135deg, #2a7f5c, #1e6047); }
      .about-value-title {
        font-size: 1.1rem;
        font-weight: 700;
        color: #15293f;
        margin-bottom: 10px;
      }
      .about-value-text {
        font-size: 0.93rem;
        color: #5a6a7c;
        line-height: 1.65;
      }

      /* ── Contact Section ── */
      .about-contact-wrap {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 60px;
        align-items: start;
      }
      .about-contact-info-title {
        font-size: 2rem;
        font-weight: 800;
        color: #15293f;
        letter-spacing: -0.02em;
        margin-bottom: 16px;
      }
      .about-contact-info-sub {
        font-size: 1rem;
        color: #5a6a7c;
        line-height: 1.65;
        margin-bottom: 28px;
      }
      .about-contact-phone-link {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        font-size: 1.45rem;
        font-weight: 800;
        color: #0f4d89;
        text-decoration: none;
        letter-spacing: -0.01em;
        transition: color 0.2s;
      }
      .about-contact-phone-link:hover { color: #0a3b69; }
      .about-contact-phone-icon {
        width: 42px; height: 42px;
        border-radius: 50%;
        background: linear-gradient(135deg, #0f4d89, #1a6abf);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
      }
      .about-contact-phone-icon svg { color: #fff; }
      .about-trust-badges {
        display: flex;
        gap: 16px;
        flex-wrap: wrap;
        margin-top: 28px;
      }
      .about-trust-badge {
        background: #f4f8fc;
        border: 1px solid #d8e3ef;
        border-radius: 30px;
        padding: 8px 16px;
        font-size: 0.82rem;
        font-weight: 600;
        color: #0f4d89;
      }

      /* ── Responsive ── */
      @media (max-width: 991px) {
        .about-hero-inner { grid-template-columns: 1fr; gap: 36px; }
        .about-hero-image-wrap { display: none; }
        .about-values-grid { grid-template-columns: 1fr; }
        .about-contact-wrap { grid-template-columns: 1fr; gap: 40px; }
        .about-stats-grid { grid-template-columns: 1fr; }
        .about-stat-item { border-right: none; border-bottom: 1px solid #e8f0f8; padding: 28px 20px; }
        .about-stat-item:last-child { border-bottom: none; }
      }
      @media (max-width: 640px) {
        .about-hero { padding: 56px 0 50px; }
        .about-hero-title { font-size: 2rem; }
        .about-values-grid { grid-template-columns: 1fr; }
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

  <body class="body-18 height-auto about-page">
    <div class="page-wrapper">

      @include('partials.navbar')
      @include('partials.header-scripts')

      {{-- Breadcrumb --}}
      <section class="section_breadcrumbs section-121">
        <div class="w-layout-blockcontainer container-default breadcrumbs-container w-container">
          <div class="breadcrumbs-wrapper">
            <a href="/" class="breadcrumb-link">Home</a>
            <div class="breadcrumb-div">/</div>
            <div class="breadcrumb-text">About Us</div>
          </div>
        </div>
      </section>

      {{-- Hero --}}
      <section class="about-hero">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="about-hero-inner">
            <div>
              <h1 class="about-hero-title">Your Trusted Door &amp; Window Experts</h1>
              <p class="about-hero-sub">
                For over 30 years, Deluxe Windows has been helping Bay Area homeowners transform their homes with high-quality windows and doors — on time, on budget, and built to last.
              </p>
              <div class="about-hero-actions">
                <a href="/gallery" class="about-hero-btn-primary">See Our Work</a>
                <a href="#contact" class="about-hero-btn-secondary">Get a Free Quote →</a>
              </div>
            </div>
            <div class="about-hero-image-wrap">
              <img
                src="https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb14fd/68459655fb9f90434a93ada6_Mask%20group5.avif"
                alt="Deluxe Windows installed project"
                loading="eager"
              />
            </div>
          </div>
        </div>
      </section>

      {{-- Stats Bar --}}
      <section class="about-stats-bar">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="about-stats-grid">
            <div class="about-stat-item">
              <span class="about-stat-number">5,000<span class="about-stat-plus">+</span></span>
              <div class="about-stat-label">Recently Completed Projects</div>
            </div>
            <div class="about-stat-item">
              <span class="about-stat-number">30<span class="about-stat-plus">+</span></span>
              <div class="about-stat-label">Years in Business</div>
            </div>
            <div class="about-stat-item">
              <span class="about-stat-number">100<span class="about-stat-plus">%</span></span>
              <div class="about-stat-label">Employee Owned</div>
            </div>
          </div>
        </div>
      </section>

      {{-- Values / Approach --}}
      <section class="section pd-120px">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="mg-top-extra-large">
            <div class="title-left---content-right" style="text-align:center;margin-bottom:48px;">
              <h2 class="display-9 mid">Why Homeowners Choose Us</h2>
              <p class="paragraph-20" style="margin-top:16px;max-width:600px;margin-left:auto;margin-right:auto;">
                We offer diverse styles, partnerships with quality manufacturers, competitive pricing, and expert installation for every Bay Area climate.
              </p>
            </div>
            <div class="about-values-grid">
              <div class="about-value-card">
                <div class="about-value-icon blue">
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                </div>
                <div class="about-value-title">20+ Years of Experience</div>
                <p class="about-value-text">With over two decades solving window and door challenges, we have seen it all — and we know how to handle every situation with professionalism and care.</p>
              </div>
              <div class="about-value-card">
                <div class="about-value-icon gold">
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                </div>
                <div class="about-value-title">Custom Design for Every Home</div>
                <p class="about-value-text">Every project receives tailored attention. We work with you to match your home's style, meet your energy efficiency goals, and stay within your budget.</p>
              </div>
              <div class="about-value-card">
                <div class="about-value-icon green">
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
                </div>
                <div class="about-value-title">City &amp; HOA Approved</div>
                <p class="about-value-text">We navigate local city regulations and homeowner association requirements so you don't have to — making the permitting process smooth and stress-free.</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      {{-- Discount Banner --}}
      <section style="background:linear-gradient(135deg,#e79a1f,#c87d14);padding:40px 0;">
        <div class="w-layout-blockcontainer container-default w-container">
          <div style="display:flex;align-items:center;justify-content:space-between;gap:24px;flex-wrap:wrap;">
            <div>
              <div style="font-size:1.9rem;font-weight:800;color:#fff;letter-spacing:-0.02em;line-height:1.1;">40% OFF Windows &amp; Doors</div>
              <div style="font-size:0.95rem;color:rgba(255,255,255,0.85);margin-top:6px;">Limited-time offer — Bay Area installations. Expires November 30, 2025.</div>
            </div>
            <a href="/special-offers" style="display:inline-block;background:#fff;color:#c87d14;padding:14px 28px;border-radius:12px;font-weight:700;font-size:0.95rem;text-decoration:none;white-space:nowrap;box-shadow:0 4px 14px rgba(0,0,0,0.15);">See All Offers</a>
          </div>
        </div>
      </section>

      {{-- Contact Form --}}
      <section class="section pd-120px" id="contact">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="about-contact-wrap">
            <div>
              <div class="about-contact-info-title">Start Your Free Consultation</div>
              <p class="about-contact-info-sub">
                Tell us about your project — we'll reach out within one business day to schedule a free in-home estimate and design consultation.
              </p>
              <a href="tel:{{ site_phone_tel() }}" class="about-contact-phone-link">
                <span class="about-contact-phone-icon">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81 19.79 19.79 0 01.01 1.18 2 2 0 012 0h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L6.09 7.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 14.92z"/></svg>
                </span>
                {{ site_phone_display() }}
              </a>
              <div class="about-trust-badges">
                <span class="about-trust-badge">✓ Free Estimates</span>
                <span class="about-trust-badge">✓ Licensed &amp; Insured</span>
                <span class="about-trust-badge">✓ Bay Area Local</span>
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

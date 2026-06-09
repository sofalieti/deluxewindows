<!DOCTYPE html>
<html
  data-wf-domain="www.deluxewindows.com"
  data-wf-page="contacts"
  data-wf-site="6841ddf8ace3d9d9facb14fd"
  lang="en"
  class="w-mod-js w-mod-ix"
>
  <head>
    <meta charset="utf-8" />
    <link href="https://cdn.prod.website-files.com" rel="preconnect" crossorigin="anonymous" />
    <title>Contact Us | Deluxe Windows – Bay Area</title>
    <meta content="Contact Deluxe Windows for window and door replacement in the Bay Area. Regional phone numbers for San Francisco, Peninsula, East Bay, South Bay, and Lamorinda." name="description" />
    <meta content="Contact Us | Deluxe Windows – Bay Area" property="og:title" />
    <meta content="Contact Deluxe Windows for window and door replacement in the Bay Area." property="og:description" />
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
      /* ── Contact Cards ── */
      .contacts-phones-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
      }
      .contact-phone-card {
        background: #fff;
        border: 1px solid #d8e3ef;
        border-radius: 16px;
        box-shadow: 0 6px 18px rgba(13,37,62,0.07);
        padding: 28px 24px;
        transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease;
        text-decoration: none;
        display: block;
      }
      .contact-phone-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 14px 32px rgba(13,37,62,0.12);
        border-color: #c4d8ec;
      }
      .contact-phone-region {
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #e79a1f;
        margin-bottom: 6px;
      }
      .contact-phone-area {
        font-size: 1.05rem;
        font-weight: 700;
        color: #15293f;
        margin-bottom: 14px;
      }
      .contact-phone-number {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 1.25rem;
        font-weight: 800;
        color: #0f4d89;
        letter-spacing: -0.01em;
      }
      .contact-phone-icon {
        width: 36px; height: 36px;
        border-radius: 50%;
        background: linear-gradient(135deg, #0f4d89, #1a6abf);
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
      }
      .contact-phone-icon svg { color: #fff; }

      /* ── Contact Layout ── */
      .contacts-main-wrap {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 60px;
        align-items: start;
      }
      .contacts-info-lead {
        font-size: 1.7rem;
        font-weight: 800;
        color: #15293f;
        letter-spacing: -0.02em;
        margin-bottom: 14px;
      }
      .contacts-info-sub {
        font-size: 0.97rem;
        color: #5a6a7c;
        line-height: 1.65;
        margin-bottom: 24px;
      }
      .contacts-badge-strip {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 32px;
      }
      .contacts-badge {
        background: #f4f8fc;
        border: 1px solid #d8e3ef;
        border-radius: 30px;
        padding: 7px 14px;
        font-size: 0.8rem;
        font-weight: 600;
        color: #0f4d89;
      }
      .contacts-main-phone {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        font-size: 1.6rem;
        font-weight: 800;
        color: #0f4d89;
        text-decoration: none;
        letter-spacing: -0.02em;
        transition: color 0.2s;
        margin-bottom: 8px;
      }
      .contacts-main-phone:hover { color: #0a3b69; }

      /* ── FAQ ── */
      .contacts-faq-grid {
        display: flex;
        flex-direction: column;
        gap: 16px;
      }
      .contacts-faq-item {
        background: #fff;
        border: 1px solid #d8e3ef;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(13,37,62,0.05);
      }
      .contacts-faq-item summary {
        padding: 22px 24px;
        font-size: 1rem;
        font-weight: 700;
        color: #15293f;
        cursor: pointer;
        list-style: none;
        display: flex;
        justify-content: space-between;
        align-items: center;
        user-select: none;
      }
      .contacts-faq-item summary::-webkit-details-marker { display: none; }
      .contacts-faq-item summary::after {
        content: '+';
        font-size: 1.4rem;
        color: #0f4d89;
        line-height: 1;
        transition: transform 0.2s;
      }
      .contacts-faq-item[open] summary::after {
        content: '−';
      }
      .contacts-faq-answer {
        padding: 0 24px 22px;
        font-size: 0.95rem;
        color: #5a6a7c;
        line-height: 1.7;
      }

      /* ── Responsive ── */
      @media (max-width: 991px) {
        .contacts-phones-grid { grid-template-columns: repeat(2, 1fr); }
        .contacts-main-wrap { grid-template-columns: 1fr; gap: 40px; }
      }
      @media (max-width: 540px) {
        .contacts-phones-grid { grid-template-columns: 1fr; }
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

  <body class="body-18 height-auto contacts-page">
    <div class="page-wrapper">

      @include('partials.navbar')
      @include('partials.header-scripts')

      {{-- Breadcrumb --}}
      <section class="section_breadcrumbs section-121">
        <div class="w-layout-blockcontainer container-default breadcrumbs-container w-container">
          <div class="breadcrumbs-wrapper">
            <a href="/" class="breadcrumb-link">Home</a>
            <div class="breadcrumb-div">/</div>
            <div class="breadcrumb-text">Contact Us</div>
          </div>
        </div>
      </section>

      {{-- Page Heading --}}
      <section class="section pd-top-80px top-none">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="mg-top-extra-large">
            <div class="title-left---content-right">
              <h1 class="display-9 mid">Contact Us</h1>
            </div>
            <div class="mg-top-small">
              <p class="paragraph-20">100% employee owned &amp; over 30 years in business. Reach your local Bay Area team directly.</p>
            </div>
          </div>
        </div>
      </section>

      {{-- Regional Phone Numbers --}}
      <section class="section" style="padding-top:16px;padding-bottom:60px;">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="contacts-phones-grid">
            <a href="tel:+14156512321" class="contact-phone-card">
              <div class="contact-phone-region">North Bay</div>
              <div class="contact-phone-area">San Francisco &amp; North Bay</div>
              <div class="contact-phone-number">
                <span class="contact-phone-icon">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81 19.79 19.79 0 01.01 1.18 2 2 0 012 0h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L6.09 7.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 14.92z"/></svg>
                </span>
                (415) 651-2321
              </div>
            </a>
            <a href="tel:+16504614446" class="contact-phone-card">
              <div class="contact-phone-region">Peninsula</div>
              <div class="contact-phone-area">San Mateo / Burlingame</div>
              <div class="contact-phone-number">
                <span class="contact-phone-icon">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81 19.79 19.79 0 01.01 1.18 2 2 0 012 0h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L6.09 7.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 14.92z"/></svg>
                </span>
                (650) 461-4446
              </div>
            </a>
            <a href="tel:+15102446500" class="contact-phone-card">
              <div class="contact-phone-region">East Bay</div>
              <div class="contact-phone-area">East Bay Area</div>
              <div class="contact-phone-number">
                <span class="contact-phone-icon">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81 19.79 19.79 0 01.01 1.18 2 2 0 012 0h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L6.09 7.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 14.92z"/></svg>
                </span>
                (510) 244-6500
              </div>
            </a>
            <a href="tel:+14085161200" class="contact-phone-card">
              <div class="contact-phone-region">South Bay</div>
              <div class="contact-phone-area">South Bay Area</div>
              <div class="contact-phone-number">
                <span class="contact-phone-icon">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81 19.79 19.79 0 01.01 1.18 2 2 0 012 0h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L6.09 7.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 14.92z"/></svg>
                </span>
                (408) 516-1200
              </div>
            </a>
            <a href="tel:+19254305135" class="contact-phone-card">
              <div class="contact-phone-region">Lamorinda</div>
              <div class="contact-phone-area">Lamorinda Area</div>
              <div class="contact-phone-number">
                <span class="contact-phone-icon">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81 19.79 19.79 0 01.01 1.18 2 2 0 012 0h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L6.09 7.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 14.92z"/></svg>
                </span>
                (925) 430-5135
              </div>
            </a>
            <div class="contact-phone-card" style="background:linear-gradient(135deg,#0f4d89,#0a3b69);border-color:transparent;display:flex;flex-direction:column;justify-content:center;">
              <div style="font-size:0.78rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:rgba(255,255,255,0.7);margin-bottom:6px;">Toll Free</div>
              <div style="font-size:1rem;font-weight:700;color:#fff;margin-bottom:14px;">Available Everywhere</div>
              <a href="tel:+18887304144" style="display:flex;align-items:center;gap:10px;font-size:1.25rem;font-weight:800;color:#fff;text-decoration:none;letter-spacing:-0.01em;">
                <span style="width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81 19.79 19.79 0 01.01 1.18 2 2 0 012 0h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L6.09 7.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 14.92z"/></svg>
                </span>
                (888) 730-4144
              </a>
            </div>
          </div>
        </div>
      </section>

      {{-- Contact Form + Info --}}
      <section class="section" style="padding-top:0;padding-bottom:80px;">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="contacts-main-wrap">
            <div>
              <div class="contacts-info-lead">Send Us a Message</div>
              <p class="contacts-info-sub">
                Fill out the form and one of our Bay Area window and door specialists will get back to you as soon as possible — usually within one business day.
              </p>
              <div class="contacts-badge-strip">
                <span class="contacts-badge">✓ Free Consultation</span>
                <span class="contacts-badge">✓ No Obligation</span>
                <span class="contacts-badge">✓ Fast Response</span>
              </div>
              <p style="font-size:0.85rem;color:#7a8fa6;line-height:1.6;">
                Prefer to call? Reach us at any of the regional numbers above, or email us any time.
              </p>
            </div>
            <div>
              @include('partials.contact-form')
            </div>
          </div>
        </div>
      </section>

      {{-- FAQ --}}
      <section class="section" style="padding-top:0;padding-bottom:100px;background:#f4f8fc;">
        <div class="w-layout-blockcontainer container-default w-container" style="padding-top:64px;">
          <h2 class="display-9 mid" style="margin-bottom:36px;">Frequently Asked Questions</h2>
          <div class="contacts-faq-grid">
            <details class="contacts-faq-item">
              <summary>What is the best window material for my home?</summary>
              <div class="contacts-faq-answer">
                The best window material depends on your home's style, climate, energy efficiency needs, and budget. Vinyl is the most popular choice for Bay Area homeowners — it's low maintenance, energy efficient, and cost effective. Fiberglass offers superior strength and insulation, while wood adds classic beauty at a higher price point. Our experts will help you choose the right material during your free consultation.
              </div>
            </details>
            <details class="contacts-faq-item">
              <summary>Do you offer free consultations?</summary>
              <div class="contacts-faq-answer">
                Yes! We offer free, no-obligation in-home consultations across the Bay Area. Our specialist will assess your windows and doors, discuss your goals, review material and brand options, and provide a detailed estimate — all at no cost to you. Just fill out the form above or call your regional number to schedule.
              </div>
            </details>
            <details class="contacts-faq-item">
              <summary>How do I know when it's time to replace my windows?</summary>
              <div class="contacts-faq-answer">
                Signs it may be time to replace your windows include drafts or air leaks, difficulty opening or closing, condensation between panes, visible damage or rot, high energy bills, or windows that are 20+ years old. We recommend a professional assessment to determine whether repair or full replacement is the right solution for your situation.
              </div>
            </details>
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

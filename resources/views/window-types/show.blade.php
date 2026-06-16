<!DOCTYPE html>
<html
  data-wf-domain="www.deluxewindows.com"
  data-wf-page="688e50676f1dbd8cba0e091a"
  data-wf-site="6841ddf8ace3d9d9facb14fd"
  data-wf-collection="688e50676f1dbd8cba0e08f2"
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
        var n = c.documentElement,
          t = " w-mod-";
        n.className += t + "js";
        ("ontouchstart" in o || (o.DocumentTouch && c instanceof DocumentTouch)) &&
          (n.className += t + "touch");
      })(window, document);
    </script>
    <link href="/webflow-assets/images/favicon.png" rel="shortcut icon" type="image/x-icon" />
    <link href="/webflow-assets/images/webclip-bg.png" rel="apple-touch-icon" />

    @include('partials.classic-layout-styles')

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
          gtag("config", "AW-1030787786");
        }
        window.addEventListener("scroll", loadGtag, { once: true });
        window.addEventListener("click", loadGtag, { once: true });
        setTimeout(loadGtag, 3000);
      })();
    </script>

    <script>
      window.$zoho = window.$zoho || {};
      $zoho.salesiq = $zoho.salesiq || { ready: function () {} };
    </script>

    <link href="https://core.service.elfsight.com/" rel="preconnect" crossorigin="" />
  </head>

  <body class="body-21">
    @include('partials.navbar')

    @include('partials.hero', [
      'windowTypeHero' => true,
      'brandLogo' => $logo,
      'heroBackgroundImage' => $featuredImage ?? '/webflow-assets/images/hero-brand-placeholder.jpg',
      'heroFormHtml' => $heroFormHtml,
      'windowHeroImage' => null,
    ])

    @include('partials.trust-badges')

    <section class="section_breadcrumbs section-121">
      <div class="w-layout-blockcontainer container-default breadcrumbs-container w-container">
        <div class="breadcrumbs-wrapper">
          <a href="/" class="breadcrumb-link">Home</a>
          <div class="breadcrumb-div">/</div>
          <a href="/windows" class="breadcrumb-link hidden-link">WINDOWS</a>
          <div class="breadcrumb-div hidden-txt">/</div>
          <a href="/brands" class="breadcrumb-link hidden-link">BRANDS</a>
          <div class="breadcrumb-div hidden-txt">/</div>
          @if($brandSlug)
          <a href="/brands/{{ $brandSlug }}" class="breadcrumb-link hidden-link">{{ $brandName }}</a>
          <div class="breadcrumb-div hidden-txt">/</div>
          @endif
          <div class="breadcrumb-text">{{ $name }}</div>
        </div>
      </div>
    </section>

    <section class="section hero-v4 padding0types wtypespadding">
      <div class="w-layout-blockcontainer container-default w-container">
        <div class="mg-top-extra-large brands">
          <div class="w-layout-grid grid-2-columns listing-grid sidebar-left">
            <div id="w-node-_6804bfc1-2fcb-3b18-3dd1-06afc16ea028-ba0e091a" class="inner-container _408px _100-mbl">
              <div class="sticky-top types">
                <section class="section_sidebar brands types">
                  @include('partials.brands-sidebar', [
                    'name' => $brandName,
                    'logo' => $logo,
                    'wfPageId' => '688e50676f1dbd8cba0e091a',
                    'hideSidebarInlineForm' => true,
                  ])
                </section>
              </div>
            </div>

            <div id="w-node-_6804bfc1-2fcb-3b18-3dd1-06afc16e9fed-ba0e091a" class="inner-container _690px _100-tablet left-sidebar">
              <div class="windows-types-header">
                <div class="logo-wrap padding0">
                  @if($logo)
                  <img src="{{ $logo }}" loading="lazy" alt="{{ $brandName }}" class="image-27 brand-mob" />
                  @endif
                </div>
                <h1 class="display-8 mid types">{{ $name }}</h1>
                <div class="mg-top-default"><div class="property-details"></div></div>
                <div class="mg-top-default"><div class="property-details"></div></div>
              </div>
              @if($aboutHtml)
              <div class="rich-text-v2 mg-bottom--16px mg-top-small-2 w-richtext">
                {!! $aboutHtml !!}
              </div>
              @endif
            </div>
          </div>
        </div>
        <div class="image-wrapper border-radius-image-default"></div>
      </div>
    </section>

    @if($collections->count() > 0)
    <div class="w-layout-blockcontainer container-default w-container">
      <div class="title-left---content-right">
        <h2 class="heading-20">{{ $collectionsTitle }}</h2>
      </div>
      <div class="mg-top-large">
        <div class="w-dyn-list">
          <div role="list" class="grid-2-columns properties-grid---v1 w-dyn-items">
            @foreach($collections as $collection)
            <div role="listitem" class="w-dyn-item">
              <a href="/brand-collections/{{ $collection['slug'] }}" class="property-wrapper-v1 w-inline-block">
                <div class="property-card-top-content-v1">
                  <div class="image-wrapper border-radius-image-default property-card-top-content-v1---image">
                    @if($collection['image'])
                    <x-img :src="$collection['image']" preset="card" loading="eager" :alt="$collection['name']" class="image cover-image" />
                    @endif
                  </div>
                </div>
                <div class="property-card-bottom-content-v1">
                  <div><h3 class="display-5">{{ $collection['name'] }}</h3></div>
                </div>
              </a>
            </div>
            @endforeach
          </div>
        </div>
      </div>
    </div>
    @endif

    <section class="section top-none">
      <div class="w-layout-blockcontainer container-default w-container">
        <div class="w-layout-grid grid-2-columns values-wrapper-grid">
          <div class="sticky-top static---tablet">
            <div class="inner-container _500px _100-tablet">
              <div class="inner-container _600px---tablet">
                <div class="mg-top-default"><h2 class="heading-8">4 Easy Steps</h2></div>
                <div class="mg-top-small">
                  <p>Our step-by-step process is designed to make replacing your windows and doors easy, stress-free, and fully tailored to your needs — from the first estimate to the final inspection.</p>
                </div>
                <div class="mg-top-default"><div class="buttons-row left"></div></div>
              </div>
            </div>
          </div>
          <div id="w-node-_40f02674-d345-7fd3-fc7e-23c4d6d6b412-ba0e091a" class="inner-container _592px _100-tablet">
            <div class="w-layout-grid grid-2-columns values-grid">
              <div class="value-wrapper">
                <div class="image-wrapper"><img src="/webflow-assets/images/684d86f32d344f16ce6ec364_flag_24dp_E3E3E3_FILL0_wght400_GRAD0_opsz24.svg" loading="eager" alt="For-architects-deluxe-windows" class="image" /></div>
                <div class="mg-top-small"><h3 class="display-5 mid">Start</h3></div>
                <div class="mg-top-extra-small"><p class="paragraph-5">Looking to replace your windows and doors? Reach out to Deluxe Windows for a complimentary estimate.</p></div>
              </div>
              <div class="value-wrapper">
                <div class="image-wrapper"><img src="/webflow-assets/images/684d86ff1fff20336f975d74_shopping_bag_24dp_E3E3E3_FILL0_wght400_GRAD0_opsz24.svg" loading="eager" alt="For-contractors-deluxe-windows" class="image" /></div>
                <div class="mg-top-small"><h3 class="display-5 mid">Manufacture</h3></div>
                <div class="mg-top-extra-small"><p class="paragraph-6">If you are satisfied with the provided estimate and approve it, we will order windows and doors according to your specifications and needs.</p></div>
              </div>
              <div class="value-wrapper">
                <div class="image-wrapper"><img src="/webflow-assets/images/684d870c533c4f729eb8094c_settings_24dp_E3E3E3_FILL0_wght400_GRAD0_opsz24.svg" loading="eager" alt="For-property-managers-owners-deluxe-windows" class="image" /></div>
                <div class="mg-top-small"><h3 class="display-5 mid">Remove and install</h3></div>
                <div class="mg-top-extra-small"><p class="paragraph-7">Once the products are ready, we will arrange a convenient time for installation and ensure your new windows and doors are expertly fitted.</p></div>
              </div>
              <div class="value-wrapper">
                <div class="image-wrapper"><img src="/webflow-assets/images/684d8718e99d2a34dfef7e4d_home_24dp_E3E3E3_FILL0_wght400_GRAD0_opsz24.svg" loading="eager" alt="For-property-managers-owners-deluxe-windows" class="image" /></div>
                <div class="mg-top-small"><h3 class="display-5 mid">Final product</h3></div>
                <div class="mg-top-extra-small"><p class="paragraph-7">Upon completion, each window and door will be thoroughly inspected to ensure they operate correctly and meet the highest standards of fit and finish.</p></div>
              </div>
              <div class="divider show-in-mbp"></div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <div>
      <div class="section-card cta-v3">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="w-layout-grid grid-2-columns cta-v3-grid">
            <div id="w-node-_497ed413-5854-1a24-5fcb-aba838af4a8e-ba0e091a" class="z-index-1">
              <div class="inner-container _500px---mbl">
                <div class="inner-container _480px">
                  <div class="inner-container _450px">
                    <div class="inner-container _300px---mbp">
                      <div class="mg-top-small"><h2 class="heading-41">Your dream home starts here.</h2></div>
                    </div>
                  </div>
                  <div class="mg-top-small">
                    <div class="text-neutral-light"><p class="paragraph-20">Tell us about your project — we’ll take care of the rest.</p></div>
                  </div>
                  <div class="mg-top-default">
                    <div class="buttons-row left">
                      <a href="/contacts" class="primary-button w-inline-block"><div class="text-block">Free Consultation</div></a>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="image-wrapper cta-v3-image">
              <x-img src="/webflow-assets/images/687ca4b70b8583ef4890bad4_iPad.avif" preset="cta" loading="eager" alt="Deluxe-windows" class="image" />
            </div>
          </div>
        </div>
      </div>
    </div>

    <section id="contact" class="section hero-v4">
      <div class="w-layout-blockcontainer container-default w-container">
        <div class="w-layout-grid grid-2-columns contact-grid-v2">
          <div id="w-node-_324983b2-578c-4b96-a818-252e8c7a83d6-ba0e091a" class="inner-container _440px _100-tablet">
            <div class="inner-container _550px---tablet">
              <h1>Contact us</h1>
              <div class="mg-top-small"><p class="paragraph-8">We’re here to help with all your door and window needs.</p></div>
            </div>
            <div class="mg-top-default">
              <div class="w-layout-grid grid-2-columns contact-links-grid-v1">
                <div class="contact-link---icon-left">
                  <img src="/webflow-assets/images/6841ddf8ace3d9d9facb1950_phone-icon-property-x-webflow-template.svg" loading="eager" alt="Phone Icon - Property X Webflow Template" class="contact-icon" />
                  <div>
                    <div class="div-block"><div class="text-block-3">Phone number</div></div>
                    <div class="mg-top-tiny">
                      <a href="tel:{{ site_phone_tel() }}" class="link mid w-inline-block"><div>{{ site_phone_display() }}</div></a>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div id="w-node-_324983b2-578c-4b96-a818-252e8c7a83e9-ba0e091a" class="inner-container _659px width-100 _100-tablet">
            <div class="form-block-2 w-form">
              <form id="email-form-2" name="email-form-2" data-name="Email Form 2" method="get" class="form-3" data-wf-page-id="688e50676f1dbd8cba0e091a" aria-label="Email Form 2">
                <div class="div-block-22">
                  <h2 class="display-4">Get Deluxe Windows for Less. 40%&nbsp;OFF* Windows</h2>
                  <label for="email-banner" class="body-14"><em class="italic-text">*Windows Replacement. Offer Expires </em><span class="date-span italic-span">{{ promotion_date('us-short') }}</span></label>
                  <label for="email-banner" class="body-14">Request a FREE No-Obligation Quote &amp; Expert Advice!</label>
                </div>
                <div class="div-block-23">
                  <div>
                    <label for="Name-2">Full name*</label>
                    <div class="input-wrapper">
                      <input class="input icon-left w-input" maxlength="256" name="Name" data-name="Name" placeholder="Full name" type="text" id="name" required="" />
                      <div class="input-line-icon-wrapper"><div class="filled-icons-font"></div></div>
                    </div>
                  </div>
                  <div id="w-node-_324983b2-578c-4b96-a818-252e8c7a83fd-ba0e091a" class="div-block-46">
                    <label for="Email-2">Email address*</label>
                    <div class="input-wrapper">
                      <input class="input icon-left w-input" maxlength="256" name="Email" data-name="Email" placeholder="example@email.com" type="email" id="email" required="" />
                      <div class="input-line-icon-wrapper"><div class="filled-icons-font"></div></div>
                    </div>
                  </div>
                  <div id="w-node-_324983b2-578c-4b96-a818-252e8c7a8405-ba0e091a">
                    <label for="Phone-2">Phone number*</label>
                    <div class="input-wrapper">
                      <input class="input icon-left w-input" maxlength="256" name="Phone" data-name="Phone" placeholder="{{ site_phone_display() }}" type="tel" id="phone" required="" />
                      <div class="input-line-icon-wrapper"><div class="filled-icons-font"></div></div>
                    </div>
                  </div>
                  <div id="w-node-_324983b2-578c-4b96-a818-252e8c7a840d-ba0e091a">
                    <label for="Company">City</label>
                    <div class="input-wrapper">
                      <input class="input icon-left w-input" maxlength="256" name="Subject" data-name="Subject" placeholder="San Francisco" type="text" id="subject" required="" />
                      <div class="input-line-icon-wrapper">
                        <img loading="eager" src="/webflow-assets/images/6841ddf8ace3d9d9facb194d_star-icon-property-x-webflow-template.svg" alt="Star Icon - Property X Webflow Template" />
                      </div>
                    </div>
                  </div>
                  <div id="w-node-_324983b2-578c-4b96-a818-252e8c7a8414-ba0e091a" class="text-area-wrapper">
                    <label for="Message-2">Listing short description</label>
                    <div class="input-wrapper">
                      <textarea id="message" name="Message" maxlength="5000" data-name="Message" placeholder="Write your message here..." required="" class="text-area icon-left w-input"></textarea>
                      <div class="text-area-icon-wrapper">
                        <img loading="eager" src="/webflow-assets/images/6841ddf8ace3d9d9facb192f_lisiting-icon-property-x-webflow-template.svg" alt="Listing Icon - Property X Webflow Template" />
                      </div>
                    </div>
                  </div>
                </div>
                <div class="primary-button space-between-v1">
                  <input type="submit" data-wait="Please wait..." class="inside-input-button text-light w-button" value="Get your free  in-home estimate" />
                </div>
              </form>
              <div class="w-form-done" tabindex="-1" role="region" aria-label="Email Form 2 success">
                <div>Thank you! Your submission has been received!</div>
              </div>
              <div class="w-form-fail" tabindex="-1" role="region" aria-label="Email Form 2 failure">
                <div>Oops! Something went wrong while submitting the form.</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    @include('partials.footer')

    <div id="menuDimmer" style="opacity: 0; pointer-events: none"></div>

    <script src="/webflow-assets/js/jquery-3.5.1.min.js" type="text/javascript"></script>
    <script src="/webflow-assets/js/webflow-brands.js" type="text/javascript"></script>

    <style>
      .scroll-block {
        overflow-y: auto;
        scrollbar-width: thin;
        scrollbar-color: #E79800 transparent;
      }
      .scroll-block::-webkit-scrollbar { width: 6px; }
      .scroll-block::-webkit-scrollbar-thumb {
        background: #E79800;
        border-radius: 999px;
      }
      .scroll-block {
        overflow-y: auto !important;
        -webkit-overflow-scrolling: touch !important;
        touch-action: pan-y !important;
        overscroll-behavior: contain;
        pointer-events: auto !important;
      }
      .w--current {
        color: #1b73bb;
        pointer-events: none;
        cursor: default;
      }
    </style>

    <script>
      (function () {
        const TRACK_PARAMS = [
          "utm_source", "utm_medium", "utm_campaign",
          "utm_term", "utm_content", "matchtype",
          "device", "creative", "gclid"
        ];
        const params = new URLSearchParams(window.location.search);
        const hasUtm = TRACK_PARAMS.some(p => params.get(p));
        if (hasUtm) {
          TRACK_PARAMS.forEach(param => {
            const value = params.get(param);
            if (value) localStorage.setItem("lead_param_" + param, value);
            else localStorage.removeItem("lead_param_" + param);
          });
        } else {
          const hasSavedUtm = TRACK_PARAMS.some(p => localStorage.getItem("lead_param_" + p));
          if (!hasSavedUtm) {
            const ref = document.referrer || "";
            let searchEngine = "", organicKeyword = "", refDomain = "";
            try {
              if (ref) {
                const refUrl = new URL(ref);
                refDomain = refUrl.hostname.replace(/^www\./, "");
                const SEO_ENGINES = {
                  "google.com": "google", "bing.com": "bing", "yahoo.com": "yahoo",
                  "duckduckgo.com": "duckduckgo", "yandex.ru": "yandex", "yandex.com": "yandex", "baidu.com": "baidu"
                };
                for (const [domain, name] of Object.entries(SEO_ENGINES)) {
                  if (refDomain.includes(domain)) {
                    searchEngine = name;
                    organicKeyword = refUrl.searchParams.get("q") || refUrl.searchParams.get("p") || refUrl.searchParams.get("query") || "(not provided)";
                    break;
                  }
                }
              }
            } catch (e) {}
            if (searchEngine) {
              localStorage.setItem("lead_param_utm_source", searchEngine);
              localStorage.setItem("lead_param_utm_medium", "organic");
              if (organicKeyword) localStorage.setItem("lead_param_utm_term", organicKeyword);
            } else if (refDomain && !refDomain.includes(window.location.hostname)) {
              localStorage.setItem("lead_param_utm_source", refDomain);
              localStorage.setItem("lead_param_utm_medium", "referral");
            } else {
              localStorage.setItem("lead_param_utm_source", "(direct)");
              localStorage.setItem("lead_param_utm_medium", "(none)");
            }
          }
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
          loadZoho();
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
              if (typeof gtag === "function") {
                gtag("event", "conversion", { send_to: "AW-1030787786/Hs9eCP7MwngQyqXC6wM" });
              }
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
        function loadZoho() {
          const script = document.createElement("script");
          script.src = "https://salesiq.zohopublic.com/widget?wc=siqfe34762ee44eb77120f2a13c55fed7c0984ca603ae60aafcaf2adda4331dc65a";
          script.defer = true;
          document.body.appendChild(script);
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
          const availableHeight = window.innerHeight - topPos - pad;
          sb.style.maxHeight = Math.max(120, availableHeight) + "px";
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
                setTimeout(() => {
                  if (other.dataset.open === "false") other.style.display = "none";
                }, 360);
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
              setTimeout(() => {
                if (list.dataset.open === "false") list.style.display = "none";
              }, 360);
              if (icon) icon.style.transform = "rotate(0deg)";
              if (sidebarIcon) sidebarIcon.style.transform = "rotate(0deg)";
            } else {
              list.style.display = "block";
              list.style.overflow = "hidden";
              list.style.maxHeight = "0px";
              requestAnimationFrame(() => { list.style.maxHeight = list.scrollHeight + "px"; });
              list.dataset.open = "true";
              if (icon) {
                icon.style.transition = "transform 0.35s ease";
                icon.style.transform = "rotate(90deg)";
              }
              if (sidebarIcon) {
                sidebarIcon.style.transition = "transform 0.35s ease";
                sidebarIcon.style.transform = "rotate(180deg)";
              }
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
          lists.forEach(l => {
            if (l.dataset.open === "true") requestAnimationFrame(() => applyScrollBlock(l));
          });
        }, { passive: true });
      })();
    </script>
  </body>
</html>

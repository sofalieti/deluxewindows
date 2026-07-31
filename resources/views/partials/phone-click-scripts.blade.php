@once
<script>
  (function () {
    const endpoint = @json(route('phone-click.store'));
    const csrfFallback = @json(csrf_token());
    const trackingParams = [
      'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term',
      'matchtype', 'device', 'creative', 'gclid', 'fbclid', 'msclkid'
    ];

    function storageGet(key) {
      try {
        return localStorage.getItem(key) || '';
      } catch (_) {
        return '';
      }
    }

    function storageSet(key, value) {
      try {
        if (value) localStorage.setItem(key, value);
      } catch (_) {
        // Ignore quota / private mode failures.
      }
    }

    function getCookie(name) {
      const match = document.cookie.match(
        new RegExp('(?:^|; )' + name.replace(/([.$?*|{}()[\]\\/+^])/g, '\\$1') + '=([^;]*)')
      );
      return match ? decodeURIComponent(match[1]) : '';
    }

    function gclidFromGoogleCookies() {
      const raw = getCookie('_gcl_aw') || getCookie('_gcl_dc') || '';
      if (!raw) return '';
      const parts = raw.split('.');
      return parts.length >= 3 ? parts.slice(2).join('.') : '';
    }

    function inferSourceFromReferrer() {
      const referrer = storageGet('lead_param_referrer') || document.referrer || '';
      if (!referrer) {
        storageSet('lead_param_utm_source', '(direct)');
        storageSet('lead_param_utm_medium', '(none)');
        return;
      }

      try {
        const referrerUrl = new URL(referrer);
        if (referrerUrl.hostname === window.location.hostname) {
          storageSet('lead_param_utm_source', '(direct)');
          storageSet('lead_param_utm_medium', '(none)');
          return;
        }

        const host = referrerUrl.hostname.replace(/^www\./i, '').toLowerCase();
        const engines = {
          'google.com': 'google',
          'bing.com': 'bing',
          'yahoo.com': 'yahoo',
          'duckduckgo.com': 'duckduckgo',
        };
        let searchEngine = '';
        Object.keys(engines).forEach(function (domain) {
          if (!searchEngine && (host === domain || host.endsWith('.' + domain))) {
            searchEngine = engines[domain];
          }
        });

        if (searchEngine) {
          storageSet('lead_param_utm_source', searchEngine);
          storageSet('lead_param_utm_medium', 'organic');
          if (!storageGet('lead_param_utm_term')) {
            const keyword = referrerUrl.searchParams.get('q')
              || referrerUrl.searchParams.get('p')
              || referrerUrl.searchParams.get('query')
              || '(not provided)';
            storageSet('lead_param_utm_term', keyword);
          }
          return;
        }

        storageSet('lead_param_utm_source', host);
        storageSet('lead_param_utm_medium', 'referral');
      } catch (_) {
        storageSet('lead_param_utm_source', '(direct)');
        storageSet('lead_param_utm_medium', '(none)');
      }
    }

    function captureTracking() {
      const params = new URLSearchParams(window.location.search);
      trackingParams.forEach(function (param) {
        const value = params.get(param);
        if (value) storageSet('lead_param_' + param, value);
      });
      if (!storageGet('lead_param_creative')) {
        const utmCreative = params.get('utm_creative');
        if (utmCreative) storageSet('lead_param_creative', utmCreative);
      }
      if (!storageGet('lead_param_gclid')) {
        const fromCookie = gclidFromGoogleCookies();
        if (fromCookie) storageSet('lead_param_gclid', fromCookie);
      }
      if (!storageGet('lead_param_landing_page')) {
        storageSet('lead_param_landing_page', window.location.pathname);
      }
      if (!storageGet('lead_param_referrer') && document.referrer) {
        try {
          const referrerUrl = new URL(document.referrer);
          if (referrerUrl.hostname !== window.location.hostname) {
            storageSet('lead_param_referrer', document.referrer);
          }
        } catch (_) {
          // Ignore malformed referrer values.
        }
      }
      if (!storageGet('lead_param_utm_source')) {
        inferSourceFromReferrer();
      }
    }

    function csrfHeaders() {
      const headers = {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      };
      const xsrf = getCookie('XSRF-TOKEN');
      if (xsrf) headers['X-XSRF-TOKEN'] = xsrf;
      const meta = document.querySelector('meta[name="csrf-token"]');
      const token = (meta && meta.getAttribute('content')) || csrfFallback || '';
      if (token) headers['X-CSRF-TOKEN'] = token;
      return headers;
    }

    function sourceLabel(anchor) {
      if (!anchor) return '';
      const explicit = (anchor.getAttribute('data-phone-source') || '').trim();
      if (explicit) return explicit;
      if (anchor.classList.contains('footer-phone-link') || anchor.closest('.footer')) return 'footer';
      if (anchor.classList.contains('hero-mobile-promo__phone')) return 'hero-mobile-promo';
      if (anchor.classList.contains('link-15') || anchor.closest('.navbar, .nav, header')) return 'navbar';
      if (anchor.classList.contains('county-bottom-cta__phone')) return 'county-bottom-cta';
      if (anchor.closest('.form-block-2, .w-form')) return 'form-area';
      const text = (anchor.textContent || '').replace(/\s+/g, ' ').trim();
      return text ? text.slice(0, 80) : 'tel-link';
    }

    function buildPayload(anchor) {
      const href = anchor.getAttribute('href') || '';
      const phone = href.replace(/^tel:/i, '').trim();
      const payload = {
        phone: phone,
        page_url: window.location.href,
        landing_page: storageGet('lead_param_landing_page') || window.location.pathname || '/',
        referrer: storageGet('lead_param_referrer') || document.referrer || '',
        source_label: sourceLabel(anchor),
        geo_location: storageGet('lead_param_geo_location') || '',
      };
      trackingParams.forEach(function (param) {
        payload[param] = storageGet('lead_param_' + param) || '';
      });
      if (!payload.gclid) {
        payload.gclid = gclidFromGoogleCookies() || '';
      }
      return payload;
    }

    function sendGa4PhoneConversion(payload) {
      if (typeof window.gtag !== 'function') return;

      window.gtag('event', 'phone_click', {
        send_to: 'G-JHYBB0THJM',
        source_label: String(payload.source_label || ''),
        page_location: String(payload.page_url || window.location.href),
        landing_page: String(payload.landing_page || ''),
        utm_source: String(payload.utm_source || ''),
        utm_medium: String(payload.utm_medium || ''),
        utm_campaign: String(payload.utm_campaign || ''),
        utm_content: String(payload.utm_content || ''),
        utm_term: String(payload.utm_term || ''),
        creative: String(payload.creative || ''),
        gclid: String(payload.gclid || ''),
        transport_type: 'beacon',
      });
    }

    function trackPhoneClick(anchor) {
      if (!anchor || anchor.dataset.phoneClickTracked === '1') return;
      anchor.dataset.phoneClickTracked = '1';
      setTimeout(function () {
        delete anchor.dataset.phoneClickTracked;
      }, 1500);

      captureTracking();
      const payload = buildPayload(anchor);
      sendGa4PhoneConversion(payload);
      try {
        fetch(endpoint, {
          method: 'POST',
          credentials: 'same-origin',
          headers: csrfHeaders(),
          body: JSON.stringify(payload),
          keepalive: true,
        }).catch(function () {});
      } catch (_) {
        // Tracking is best-effort; do not block the call.
      }
    }

    captureTracking();

    document.addEventListener('click', function (event) {
      const anchor = event.target && event.target.closest
        ? event.target.closest('a[href^="tel:"], a[href^="TEL:"]')
        : null;
      if (!anchor) return;
      trackPhoneClick(anchor);
    }, true);
  })();
</script>
@endonce

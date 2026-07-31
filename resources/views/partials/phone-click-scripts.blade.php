@once
<script>
  (function () {
    const endpoint = @json(route('phone-click.store'));
    const csrfFallback = @json(csrf_token());

    function getCookie(name) {
      const match = document.cookie.match(
        new RegExp('(?:^|; )' + name.replace(/([.$?*|{}()[\]\\/+^])/g, '\\$1') + '=([^;]*)')
      );
      return match ? decodeURIComponent(match[1]) : '';
    }

    function storageGet(key) {
      try {
        return localStorage.getItem(key) || '';
      } catch (_) {
        return '';
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

    function attributionFields() {
      if (window.DeluxeAttribution && typeof window.DeluxeAttribution.capture === 'function') {
        window.DeluxeAttribution.capture();
        return window.DeluxeAttribution.payloadFields();
      }
      return {};
    }

    function buildPayload(anchor) {
      const href = anchor.getAttribute('href') || '';
      const phone = href.replace(/^tel:/i, '').trim();
      const attribution = attributionFields();

      return Object.assign({}, attribution, {
        phone: phone,
        page_url: window.location.href,
        source_label: sourceLabel(anchor),
        geo_location: storageGet('lead_param_geo_location') || '',
      });
    }

    function sendGa4PhoneConversion(payload) {
      if (typeof window.gtag !== 'function') return;

      window.gtag('event', 'phone_click', {
        send_to: 'G-JHYBB0THJM',
        source_label: String(payload.source_label || ''),
        page_location: String(payload.page_url || window.location.href),
        landing_page: String(payload.landing_page || ''),
        first_landing_page: String(payload.first_landing_page || ''),
        utm_source: String(payload.utm_source || ''),
        utm_medium: String(payload.utm_medium || ''),
        utm_campaign: String(payload.utm_campaign || ''),
        first_utm_source: String(payload.first_utm_source || ''),
        first_utm_medium: String(payload.first_utm_medium || ''),
        gclid: String(payload.gclid || ''),
        first_gclid: String(payload.first_gclid || ''),
        transport_type: 'beacon',
      });
    }

    function trackPhoneClick(anchor) {
      if (!anchor || anchor.dataset.phoneClickTracked === '1') return;
      anchor.dataset.phoneClickTracked = '1';
      setTimeout(function () {
        delete anchor.dataset.phoneClickTracked;
      }, 1500);

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

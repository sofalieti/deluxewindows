@once
<script>
  (function () {
    const endpoint = @json(route('phone-click.store'));
    const callbackEndpoint = @json(route('callback-request.store'));
    const csrfFallback = @json(csrf_token());

    let activeAnchor = null;
    let lastFocus = null;

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

    function isBingAdsVisitor() {
      const a = attributionFields();
      if (String(a.msclkid || '').trim() || String(a.first_msclkid || '').trim()) {
        return true;
      }
      const source = String(a.utm_source || a.first_utm_source || '').toLowerCase();
      const medium = String(a.utm_medium || a.first_utm_medium || '').toLowerCase();
      const isBingSource = source === 'bing'
        || source === 'msn'
        || source.indexOf('bing') !== -1
        || source.indexOf('microsoft') !== -1;
      const isPaid = /(?:^|[_\-\s])(cpc|ppc|paid|paidsearch|sem)(?:$|[_\-\s])/.test(medium);
      return isBingSource && isPaid;
    }

    function buildPayload(anchor, sourceOverride) {
      const href = (anchor && anchor.getAttribute('href')) || '';
      const phone = href.replace(/^tel:/i, '').trim();
      const attribution = attributionFields();

      return Object.assign({}, attribution, {
        phone: phone,
        page_url: window.location.href,
        source_label: sourceOverride || sourceLabel(anchor),
        geo_location: storageGet('lead_param_geo_location') || '',
        hero_variant: String((document.body && document.body.dataset.heroVariant) || ''),
      });
    }

    function sendGa4PhoneConversion(payload) {
      if (typeof window.gtag !== 'function') return;

      window.gtag('event', 'phone_click', {
        send_to: 'G-JHYBB0THJM',
        source_label: String(payload.source_label || ''),
        hero_variant: String(payload.hero_variant || ''),
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

    function trackPhoneClick(anchor, sourceOverride) {
      if (!anchor) return;
      if (!sourceOverride && anchor.dataset.phoneClickTracked === '1') return;
      if (!sourceOverride) {
        anchor.dataset.phoneClickTracked = '1';
        setTimeout(function () {
          delete anchor.dataset.phoneClickTracked;
        }, 1500);
      }

      const payload = buildPayload(anchor, sourceOverride);
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

    let contactBackPanel = 'choice';

    const contactCopy = {
      callback: {
        title: 'Request a callback',
        copy: 'Leave your phone number. Hours: <strong>Mon–Fri, 9:00 AM – 7:00 PM (PT)</strong>. Closed weekends — we’ll call you back.',
        success: 'Thanks — we’ll call you back soon.',
        formId: 'bing-phone-callback',
      },
      sms: {
        title: 'Text you on SMS',
        copy: 'Leave your phone number and we’ll text you. Hours: <strong>Mon–Fri, 9:00 AM – 7:00 PM (PT)</strong>. Closed weekends — we’ll still reach out.',
        success: 'Thanks — we’ll text you soon.',
        formId: 'bing-phone-sms',
      },
      whatsapp: {
        title: 'Message you on WhatsApp',
        copy: 'Leave your phone number and we’ll message you on WhatsApp. Hours: <strong>Mon–Fri, 9:00 AM – 7:00 PM (PT)</strong>. Closed weekends — we’ll still reach out.',
        success: 'Thanks — we’ll message you on WhatsApp soon.',
        formId: 'bing-phone-whatsapp',
      },
    };

    function modalEl() {
      return document.getElementById('bingPhoneChoiceModal');
    }

    function showPanel(name) {
      const modal = modalEl();
      if (!modal) return;
      modal.querySelectorAll('[data-bing-phone-panel]').forEach(function (panel) {
        panel.hidden = panel.getAttribute('data-bing-phone-panel') !== name;
      });
    }

    function openContactPanel(channel, backPanel) {
      const modal = modalEl();
      if (!modal) return;
      const conf = contactCopy[channel] || contactCopy.callback;
      contactBackPanel = backPanel || 'choice';
      const channelInput = modal.querySelector('[data-bing-phone-channel]');
      const titleEl = modal.querySelector('[data-bing-phone-contact-title]');
      const copyEl = modal.querySelector('[data-bing-phone-contact-copy]');
      const successEl = modal.querySelector('[data-bing-phone-success]');
      const errorEl = modal.querySelector('[data-bing-phone-error]');
      const form = modal.querySelector('[data-bing-phone-contact-form]');
      if (channelInput) channelInput.value = channel;
      if (titleEl) titleEl.textContent = conf.title;
      if (copyEl) copyEl.innerHTML = conf.copy;
      if (successEl) {
        successEl.textContent = conf.success;
        successEl.hidden = true;
      }
      if (errorEl) {
        errorEl.hidden = true;
        errorEl.textContent = '';
      }
      if (form) {
        const phoneInput = form.querySelector('input[name="phone"]');
        if (phoneInput) phoneInput.value = '';
      }
      showPanel('contact');
      const input = modal.querySelector('#bingPhoneContactInput');
      if (input) input.focus();
    }

    function openModal(anchor) {
      const modal = modalEl();
      if (!modal) return false;
      activeAnchor = anchor;
      lastFocus = document.activeElement;
      modal.hidden = false;
      modal.setAttribute('aria-hidden', 'false');
      document.body.classList.add('bing-phone-choice-open');
      showPanel('choice');
      contactBackPanel = 'choice';
      const err = modal.querySelector('[data-bing-phone-error]');
      const ok = modal.querySelector('[data-bing-phone-success]');
      if (err) {
        err.hidden = true;
        err.textContent = '';
      }
      if (ok) ok.hidden = true;
      const form = modal.querySelector('[data-bing-phone-contact-form]');
      if (form) form.reset();
      const firstBtn = modal.querySelector('[data-bing-phone-action="call"]');
      if (firstBtn) firstBtn.focus();
      return true;
    }

    function closeModal() {
      const modal = modalEl();
      if (!modal) return;
      modal.hidden = true;
      modal.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('bing-phone-choice-open');
      activeAnchor = null;
      if (lastFocus && typeof lastFocus.focus === 'function') {
        try { lastFocus.focus(); } catch (_) {}
      }
      lastFocus = null;
    }

    function callNow() {
      const anchor = activeAnchor;
      if (!anchor) {
        closeModal();
        return;
      }
      trackPhoneClick(anchor);
      const href = anchor.getAttribute('href') || '';
      closeModal();
      if (href) {
        window.location.href = href;
      }
    }

    async function submitContact(form) {
      const modal = modalEl();
      const phoneInput = form.querySelector('input[name="phone"]');
      const channelInput = form.querySelector('[data-bing-phone-channel]');
      const errorEl = modal ? modal.querySelector('[data-bing-phone-error]') : null;
      const successEl = modal ? modal.querySelector('[data-bing-phone-success]') : null;
      const submitBtn = form.querySelector('[data-bing-phone-submit]');
      const phone = phoneInput ? String(phoneInput.value || '').trim() : '';
      const channel = channelInput ? String(channelInput.value || 'callback').trim() : 'callback';
      const conf = contactCopy[channel] || contactCopy.callback;

      if (errorEl) {
        errorEl.hidden = true;
        errorEl.textContent = '';
      }
      if (successEl) successEl.hidden = true;

      if (!phone || phone.replace(/\D+/g, '').length < 10) {
        if (errorEl) {
          errorEl.textContent = 'Please enter a valid phone number.';
          errorEl.hidden = false;
        }
        return;
      }

      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Sending…';
      }

      const attribution = attributionFields();
      const payload = Object.assign({}, attribution, {
        phone: phone,
        channel: channel,
        page_url: window.location.href,
        geo_location: storageGet('lead_param_geo_location') || '',
        form_id: conf.formId,
      });

      try {
        const res = await fetch(callbackEndpoint, {
          method: 'POST',
          credentials: 'same-origin',
          headers: csrfHeaders(),
          body: JSON.stringify(payload),
        });
        if (!res.ok) {
          throw new Error('Contact submit failed');
        }
        const data = await res.json().catch(function () { return { ok: true }; });
        const isSpam = !!(data && data.spam);
        if (!isSpam) {
          if (typeof window.gtag_report_conversion === 'function') {
            window.gtag_report_conversion(undefined, {
              email: '',
              phone: phone,
            });
          }
          if (typeof window.gtag === 'function') {
            window.gtag('event', 'generate_lead', {
              send_to: 'G-JHYBB0THJM',
              form_id: conf.formId,
              transport_type: 'beacon',
            });
          }
        }
        if (successEl) {
          successEl.textContent = conf.success;
          successEl.hidden = false;
        }
        if (phoneInput) phoneInput.value = '';
        setTimeout(closeModal, 1600);
      } catch (_) {
        if (errorEl) {
          errorEl.textContent = 'Something went wrong. Please try again or call us.';
          errorEl.hidden = false;
        }
      } finally {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.textContent = 'Send request';
        }
      }
    }

    function bindModal() {
      const modal = modalEl();
      if (!modal || modal.dataset.bound === '1') return;
      modal.dataset.bound = '1';

      modal.addEventListener('click', function (event) {
        const target = event.target;
        if (!(target instanceof Element)) return;

        if (target.closest('[data-bing-phone-close]')) {
          closeModal();
          return;
        }

        const actionBtn = target.closest('[data-bing-phone-action]');
        if (!actionBtn) return;
        const action = actionBtn.getAttribute('data-bing-phone-action');
        if (action === 'call') {
          callNow();
        } else if (action === 'callback') {
          openContactPanel('callback', 'choice');
        } else if (action === 'text') {
          showPanel('text');
        } else if (action === 'sms') {
          openContactPanel('sms', 'text');
        } else if (action === 'whatsapp') {
          openContactPanel('whatsapp', 'text');
        } else if (action === 'back') {
          showPanel('choice');
        } else if (action === 'back-contact') {
          showPanel(contactBackPanel || 'choice');
        }
      });

      const form = modal.querySelector('[data-bing-phone-contact-form]');
      if (form) {
        form.addEventListener('submit', function (event) {
          event.preventDefault();
          submitContact(form);
        });
      }

      document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal && !modal.hidden) {
          closeModal();
        }
      });
    }

    document.addEventListener('click', function (event) {
      const anchor = event.target && event.target.closest
        ? event.target.closest('a[href^="tel:"], a[href^="TEL:"]')
        : null;
      if (!anchor) return;

      if (isBingAdsVisitor() && modalEl()) {
        event.preventDefault();
        event.stopPropagation();
        bindModal();
        openModal(anchor);
        return;
      }

      trackPhoneClick(anchor);
    }, true);

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', bindModal);
    } else {
      bindModal();
    }
  })();
</script>
@endonce

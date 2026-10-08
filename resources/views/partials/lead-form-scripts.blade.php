@once
<script>
  (function () {
    const endpoint = @json(route('contact.submit'));
    const csrfFallback = @json(csrf_token());
    const googleBridges = @json(array_values(array_filter((array) config('services.lead_bridge.urls', []))));
    const trackingParams = [
      'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term',
      'matchtype', 'device', 'creative', 'gclid', 'fbclid', 'msclkid'
    ];
    let geoLocationPromise = null;

    function storageGet(key) {
      try {
        return localStorage.getItem(key) || '';
      } catch (_) {
        return '';
      }
    }

    function storageSet(key, value) {
      try {
        localStorage.setItem(key, value);
      } catch (_) {
        // Tracking storage can be unavailable in privacy mode.
      }
    }

    function getCookie(name) {
      const match = document.cookie.match(
        new RegExp('(?:^|; )' + name.replace(/([.$?*|{}()[\]\\/+^])/g, '\\$1') + '=([^;]*)')
      );
      return match ? decodeURIComponent(match[1]) : '';
    }

    function csrfToken() {
      const meta = document.querySelector('meta[name="csrf-token"]');
      const fromMeta = meta && meta.getAttribute('content');
      return fromMeta || csrfFallback || '';
    }

    function csrfHeaders() {
      const headers = {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      };
      const xsrf = getCookie('XSRF-TOKEN');
      if (xsrf) {
        // Prefer live cookie — stays valid as long as the session cookie does.
        headers['X-XSRF-TOKEN'] = xsrf;
      }
      const token = csrfToken();
      if (token) {
        headers['X-CSRF-TOKEN'] = token;
      }
      return headers;
    }

    async function refreshCsrfSession() {
      try {
        const res = await fetch(window.location.pathname + window.location.search, {
          method: 'GET',
          credentials: 'same-origin',
          headers: {
            Accept: 'text/html',
            'X-Requested-With': 'XMLHttpRequest',
          },
          cache: 'no-store',
        });
        const html = await res.text();
        const match = html.match(/<meta\s+name=["']csrf-token["']\s+content=["']([^"']+)["']/i);
        if (match && match[1]) {
          const meta = document.querySelector('meta[name="csrf-token"]');
          if (meta) {
            meta.setAttribute('content', match[1]);
          }
        }
      } catch (_) {
        // Retry will still attempt with whatever cookie/token is available.
      }
    }

    async function postLead(payload, allowRetry) {
      const res = await fetch(endpoint, {
        method: 'POST',
        credentials: 'same-origin',
        headers: csrfHeaders(),
        body: JSON.stringify(payload),
      });

      if (res.status === 419 && allowRetry) {
        await refreshCsrfSession();
        return postLead(payload, false);
      }

      return res;
    }

    function captureTracking() {
      if (window.DeluxeAttribution && typeof window.DeluxeAttribution.capture === 'function') {
        window.DeluxeAttribution.capture();
      }
    }

    function getGeoLocation() {
      if (!geoLocationPromise) {
        geoLocationPromise = Promise.race([
          fetch('https://ipapi.co/json/')
            .then(function (response) {
              return response.ok ? response.json() : {};
            })
            .then(function (data) {
              return typeof data.city === 'string' ? data.city : '';
            })
            .catch(function () {
              return '';
            }),
          new Promise(function (resolve) {
            setTimeout(function () { resolve(''); }, 1200);
          })
        ]);
      }
      return geoLocationPromise;
    }

    function toPayload(form, geoLocation) {
      const fd = new FormData(form);
      const firstValue = function (names) {
        for (const name of names) {
          const value = fd.get(name);
          if (typeof value === 'string' && value.trim() !== '') {
            return value.trim();
          }
        }
        return '';
      };

      const pageUrl = window.location.href;
      const pagePath = window.location.pathname || '/';

      const formId = firstValue(['Form ID', 'form_id'])
        || (form.getAttribute('data-form-id') || '').trim()
        || resolveFormId(pagePath);

      const attribution = (window.DeluxeAttribution && typeof window.DeluxeAttribution.payloadFields === 'function')
        ? window.DeluxeAttribution.payloadFields()
        : {};

      const payload = Object.assign({}, attribution, {
        hero_variant: String((document.body && document.body.dataset.heroVariant) || ''),
        Name: firstValue(['Name', 'full_name', 'name']),
        Email: firstValue(['Email', 'email']),
        Phone: firstValue(['Phone', 'phone']),
        Subject: firstValue(['Subject', 'city', 'City', 'Company']),
        Message: firstValue(['Message', 'message', 'Description', 'description']),
        'Form ID': formId,
        form_id: formId,
        Page: pageUrl,
        page_url: pageUrl,
        URL: pageUrl,
        landing_page: firstValue(['landing_page']) || attribution.landing_page || storageGet('lead_param_landing_page') || pagePath,
        referrer: firstValue(['referrer']) || attribution.referrer || storageGet('lead_param_referrer') || document.referrer,
        geo_location: geoLocation,
      });
      trackingParams.forEach(function (param) {
        payload[param] = firstValue([param]) || payload[param] || storageGet('lead_param_' + param);
      });
      return payload;
    }

    function sendGa4LeadConversion(payload) {
      if (typeof window.gtag !== 'function') return;

      window.gtag('event', 'generate_lead', {
        send_to: 'G-JHYBB0THJM',
        currency: 'USD',
        value: 1,
        form_id: String(payload.form_id || payload['Form ID'] || ''),
        hero_variant: String(payload.hero_variant || ''),
        page_location: String(payload.page_url || payload.Page || window.location.href),
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

    /**
     * Direct browser → Google Apps Script (same path that historically filled the sheet).
     * URLSearchParams keeps keys like "Form ID" intact (unlike PHP http_build_query).
     */
    function postToGoogleBridges(payload) {
      if (!Array.isArray(googleBridges) || googleBridges.length === 0) {
        return;
      }

      const formId = String(payload['Form ID'] || payload.form_id || '').trim();
      const pageUrl = String(payload.Page || payload.page_url || payload.URL || window.location.href || '').trim();
      const pagePath = (function () {
        try {
          return pageUrl ? (new URL(pageUrl)).pathname : (window.location.pathname || '/');
        } catch (_) {
          return window.location.pathname || '/';
        }
      })();

      const body = new URLSearchParams();
      body.append('Form ID', formId);
      body.append('Page', pageUrl);
      body.append('URL', pageUrl);
      body.append('Name', String(payload.Name || ''));
      body.append('Email', String(payload.Email || ''));
      body.append('Phone', String(payload.Phone || ''));
      body.append('Subject', String(payload.Subject || ''));
      body.append('Message', String(payload.Message || ''));
      body.append('landing_page', String(payload.landing_page || pagePath));
      body.append('referrer', String(payload.referrer || ''));
      body.append('geo_location', String(payload.geo_location || ''));
      trackingParams.forEach(function (param) {
        body.append(param, String(payload[param] || ''));
      });

      googleBridges.forEach(function (url) {
        if (!url) return;
        try {
          fetch(url, {
            method: 'POST',
            body: body,
            keepalive: true,
          });
        } catch (_) {
          // Sheet write is best-effort; Laravel still stores the lead.
        }
      });
    }

    function titleCaseSlug(slug, stripSuffix) {
      let value = String(slug || '').toLowerCase();
      if (stripSuffix && value.endsWith('-' + stripSuffix)) {
        value = value.slice(0, -(stripSuffix.length + 1));
      }
      return value
        .split(/[-_]+/)
        .filter(Boolean)
        .map(function (word) {
          if (word === 'and' || word === 'vs') return word;
          return word.charAt(0).toUpperCase() + word.slice(1);
        })
        .join(' ') || 'Page';
    }

    function resolveFormId(pathname) {
      const path = '/' + String(pathname || '/').replace(/^\/+|\/+$/g, '');
      if (path === '/') return 'Home Page Form';

      const parts = path.replace(/^\/+|\/+$/g, '').split('/');
      const first = parts[0] || '';
      const slug = parts[1] || '';

      switch (first) {
        case 'windows':
          return slug ? titleCaseSlug(slug, 'windows') + ' Page Form' : 'Windows Index Form';
        case 'doors':
          return slug ? titleCaseSlug(slug, 'doors') + ' Page Form' : 'Doors Index Form';
        case 'brands':
          return titleCaseSlug(slug) + ' Window Form';
        case 'door-brands':
          return titleCaseSlug(slug) + ' Door Form';
        case 'window-type':
          return titleCaseSlug(slug) + ' Form';
        case 'door-types':
          return titleCaseSlug(slug) + ' Form';
        case 'brand-collections':
          return titleCaseSlug(slug.replace(/^brand-/, '')) + ' Collection Form';
        case 'window-replacement':
          return titleCaseSlug(slug) + ' Window Replacement Form';
        case 'county-hub-pages':
          return titleCaseSlug(slug) + ' County Hub Form';
        case 'blog':
          return slug ? titleCaseSlug(slug) + ' Blog Form' : 'Blog Index Form';
        case 'brand':
          return 'Brands Catalog Form';
        case 'contacts':
          return 'Contacts Page Form';
        case 'about':
          return 'About Page Form';
        case 'financing':
          return 'Financing Page Form';
        case 'special-offers':
          return 'Special Offers Form';
        case 'gallery':
          return 'Gallery Page Form';
        case 'faq':
          return 'FAQ Page Form';
        case 'testimonials':
          return 'Testimonials Page Form';
        case 'glossary':
          return 'Glossary Page Form';
        default:
          return titleCaseSlug(slug || first) + ' Page Form';
      }
    }

    const referralOffer = @json([
      'reward' => (int) config('referral.reward_amount', 150),
      'credit' => (int) config('referral.friend_credit_amount', 150),
      'join' => url('/referrals').'#apply',
      'login' => route('platform.referral.my-dashboard'),
    ]);

    function escapeHtml(value) {
      return String(value || '').replace(/[&<>"']/g, function (ch) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
      });
    }

    function ensureReferralPromoStyles() {
      if (document.getElementById('lead-ref-promo-css')) return;
      const style = document.createElement('style');
      style.id = 'lead-ref-promo-css';
      style.textContent = [
        '.lead-thanks{display:grid;gap:12px;text-align:left;color:#14263a;font-size:15px;line-height:1.5}',
        '.lead-thanks__head{padding:14px 16px;border-radius:12px;background:#edf9f2;border:1px solid #9fd8bb;color:#1c5c3d}',
        '.lead-thanks__head b{display:block;font-size:17px;color:#14402b}',
        '.lead-ref-promo{padding:16px;border-radius:14px;background:#fff;border:2px dashed #e87722;box-shadow:0 14px 30px -20px rgba(8,68,111,.55)}',
        '.lead-ref-promo__kicker{margin:0 0 4px;color:#cf6514;font-size:11px;font-weight:800;letter-spacing:.1em;text-transform:uppercase}',
        '.lead-ref-promo__title{margin:0 0 6px;color:#08446f;font-size:20px;font-weight:800;line-height:1.15}',
        '.lead-ref-promo__text{margin:0 0 12px;color:#4a5a6a;font-size:14px}',
        '.lead-ref-promo__list{margin:0 0 14px;padding:0;list-style:none;display:grid;gap:4px;font-size:13.5px;color:#14263a}',
        '.lead-ref-promo__list li:before{content:"\\2713  ";color:#1f8a5b;font-weight:800}',
        '.lead-ref-promo__btn{display:block;padding:12px 16px;border-radius:999px;background:#e87722;color:#fff !important;font-weight:700;text-align:center;text-decoration:none}',
        '.lead-ref-promo__btn:hover{background:#cf6514}',
        '.lead-ref-promo__login{margin:10px 0 0;font-size:12.5px;color:#4a5a6a;text-align:center}',
        '.lead-ref-promo__login a{color:#0b5a92;font-weight:700}'
      ].join('');
      document.head.appendChild(style);
    }

    function rememberReferralPrefill(form) {
      try {
        const value = function (names) {
          for (let i = 0; i < names.length; i++) {
            const el = form.elements.namedItem(names[i]);
            if (el && typeof el.value === 'string' && el.value.trim()) return el.value.trim();
          }
          return '';
        };
        sessionStorage.setItem('dwReferralPrefill', JSON.stringify({
          full_name: value(['Name', 'full_name', 'name']),
          email: value(['Email', 'email']),
          phone: value(['Phone', 'phone']),
        }));
      } catch (_) {
        // Prefill is a convenience only.
      }
    }

    function referralThanksHtml(form, keepText) {
      const nameField = form.elements.namedItem('Name') || form.elements.namedItem('full_name') || form.elements.namedItem('name');
      const first = nameField && typeof nameField.value === 'string' ? nameField.value.trim().split(/\s+/)[0] : '';
      const reward = referralOffer.reward;
      const credit = referralOffer.credit;
      const head = keepText
        ? escapeHtml(keepText)
        : '<b>Thank you' + (first ? ', ' + escapeHtml(first) : '') + '! Your request is in.</b>'
          + 'A specialist will call you within one business day to set up your free estimate.';

      return '<div class="lead-thanks">'
        + '<div class="lead-thanks__head" role="status">' + head + '</div>'
        + '<div class="lead-ref-promo">'
        + '<p class="lead-ref-promo__kicker">While you wait · Give $' + credit + ', get $' + reward + '</p>'
        + '<p class="lead-ref-promo__title">Know a neighbor who needs windows?</p>'
        + '<p class="lead-ref-promo__text">Join our referral program — they get <b>$' + credit + ' off</b>, you get <b>$' + reward
        + '</b> by Zelle or Venmo after their install. Free, no limits, no fine print.</p>'
        + '<ul class="lead-ref-promo__list">'
        + '<li>Your personal link &amp; QR code</li>'
        + '<li>Printable poster, flyers &amp; business cards</li>'
        + '<li>Dashboard to track every referral and payout</li>'
        + '</ul>'
        + '<a class="lead-ref-promo__btn" href="' + escapeHtml(referralOffer.join) + '">Get my referral link →</a>'
        + '<p class="lead-ref-promo__login">Already a partner? <a href="' + escapeHtml(referralOffer.login) + '">Log in to your dashboard</a></p>'
        + '</div>'
        + '</div>';
    }

    function showState(form, ok) {
      if (ok) {
        ensureReferralPromoStyles();
        rememberReferralPrefill(form);
      }
      const wrapper = form.closest('.w-form');
      if (!wrapper) {
        let status = form.parentElement
          ? form.parentElement.querySelector('[data-lead-form-status]')
          : null;
        if (!status && form.parentElement) {
          status = document.createElement('div');
          status.dataset.leadFormStatus = '1';
          status.setAttribute('role', 'status');
          form.parentElement.insertBefore(status, form);
        }
        if (status) {
          status.className = ok ? 'contact-form-success' : 'contact-form-error';
          if (ok) {
            status.innerHTML = referralThanksHtml(form, '');
          } else {
            status.textContent = 'Oops! Something went wrong while submitting the form.';
          }
        }
        if (ok) form.hidden = true;
        return;
      }
      const done = wrapper.querySelector('.w-form-done');
      const fail = wrapper.querySelector('.w-form-fail');
      if (ok && done && !done.querySelector('.lead-thanks')) {
        const keepText = done.hasAttribute('data-keep-done') ? done.textContent.trim() : '';
        done.innerHTML = referralThanksHtml(form, keepText);
      }
      if (done) done.style.display = ok ? 'block' : 'none';
      if (fail) fail.style.display = ok ? 'none' : 'block';
      if (ok) form.style.display = 'none';
    }

    function isLeadForm(form) {
      const hasField = function (names) {
        return names.some(function (name) {
          return form.elements.namedItem(name) !== null;
        });
      };

      // Email is optional (hidden on mobile); name + phone identify lead forms.
      return hasField(['Name', 'full_name', 'name'])
        && hasField(['Phone', 'phone']);
    }

    function adaptLeadFormsForViewport() {
      try {
        const mobile = window.matchMedia('(max-width: 767px)').matches;

        document.querySelectorAll('form').forEach(function (form) {
          if (!isLeadForm(form)) return;

          form.querySelectorAll('input[name="Email"], input[name="email"]').forEach(function (input) {
            if (mobile) {
              if (input.hasAttribute('required') && input.dataset.wasRequired !== '0') {
                input.dataset.wasRequired = '1';
              }
              input.removeAttribute('required');
              input.setAttribute('tabindex', '-1');
              input.setAttribute('aria-hidden', 'true');
            } else {
              if (input.dataset.wasRequired === '1') {
                input.setAttribute('required', '');
              }
              input.removeAttribute('tabindex');
              input.removeAttribute('aria-hidden');
            }
          });

          // Browser / OS contact autofill on phones (Safari, Chrome Android).
          if (!mobile) return;
          form.querySelectorAll('input[name="Phone"], input[name="phone"], input[type="tel"]').forEach(function (input) {
            if (!input.getAttribute('autocomplete')) {
              input.setAttribute('autocomplete', 'tel');
            }
            input.setAttribute('inputmode', 'tel');
          });
        });
      } catch (_) {
        // Never block the rest of the page if form adaptation fails.
      }
    }

    function scheduleLeadFormAdapt() {
      adaptLeadFormsForViewport();
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', scheduleLeadFormAdapt, { once: true });
    } else {
      scheduleLeadFormAdapt();
    }
    if (typeof window.matchMedia === 'function') {
      const mq = window.matchMedia('(max-width: 767px)');
      if (typeof mq.addEventListener === 'function') {
        mq.addEventListener('change', scheduleLeadFormAdapt);
      } else if (typeof mq.addListener === 'function') {
        mq.addListener(scheduleLeadFormAdapt);
      }
    }

    function submitHost(btn) {
      if (btn.parentElement && btn.parentElement.classList.contains('lead-form-submit-host')) {
        return btn.parentElement;
      }
      const host = document.createElement('span');
      host.className = 'lead-form-submit-host';
      btn.replaceWith(host);
      host.appendChild(btn);
      return host;
    }

    function setSubmitLoading(btn, loading) {
      if (!btn) return;
      const host = submitHost(btn);
      let spinner = host.querySelector('.lead-form-spinner');

      if (loading) {
        if (!spinner) {
          spinner = document.createElement('span');
          spinner.className = 'lead-form-spinner';
          spinner.setAttribute('aria-hidden', 'true');
          host.appendChild(spinner);
        }
        host.classList.add('is-loading');
        btn.classList.add('is-loading');
        btn.disabled = true;
        btn.setAttribute('aria-busy', 'true');
        if (btn.tagName === 'INPUT') {
          if (!btn.dataset.originalValue) {
            btn.dataset.originalValue = btn.value;
          }
          btn.value = btn.getAttribute('data-wait') || 'Please wait...';
        } else if (!btn.dataset.originalHtml) {
          btn.dataset.originalHtml = btn.innerHTML;
          btn.innerHTML = '<span class="lead-form-btn-label">Please wait...</span>';
        }
        return;
      }

      host.classList.remove('is-loading');
      btn.classList.remove('is-loading');
      btn.disabled = false;
      btn.removeAttribute('aria-busy');
      if (btn.tagName === 'INPUT' && btn.dataset.originalValue) {
        btn.value = btn.dataset.originalValue;
      } else if (btn.dataset.originalHtml) {
        btn.innerHTML = btn.dataset.originalHtml;
        delete btn.dataset.originalHtml;
      }
    }

    async function submitLead(form) {
      const submitBtn = form.querySelector('input[type="submit"], button[type="submit"]');
      setSubmitLoading(submitBtn, true);
      try {
        const payload = toPayload(form, await getGeoLocation());
        // Laravel first (spam gate). Google sheet + Ads conversion only for clean leads.
        // Fresh CSRF from cookie/meta; auto-retry once on 419 (expired tab/session).
        const res = await postLead(payload, true);
        if (!res.ok) {
          throw new Error('Lead submit failed');
        }
        const data = await res.json().catch(function () { return { ok: true }; });
        const isSpam = !!(data && data.spam);
        if (!isSpam) {
          postToGoogleBridges(payload);
          sendGa4LeadConversion(payload);
          if (typeof window.gtag_report_conversion === 'function') {
            window.gtag_report_conversion(undefined, {
              email: payload.Email || payload.email || '',
              phone: payload.Phone || payload.phone || '',
            });
          }
        }
        showState(form, true);
      } catch (_) {
        showState(form, false);
        setSubmitLoading(submitBtn, false);
      } finally {
        form.dataset.laravelLeadSubmitting = '0';
      }
    }

    document.addEventListener('submit', function (e) {
      const form = e.target;
      if (
        !(form instanceof HTMLFormElement)
        || form.matches('[data-no-lead]')
        || !isLeadForm(form)
      ) {
        return;
      }
      if (form.dataset.laravelLeadSubmitting === '1') {
        e.preventDefault();
        return;
      }

      e.preventDefault();
      e.stopImmediatePropagation();
      form.dataset.laravelLeadSubmitting = '1';
      submitLead(form);
    }, true);

    captureTracking();
  })();
</script>
@endonce

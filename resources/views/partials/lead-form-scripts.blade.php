@once
<script>
  (function () {
    const endpoint = @json(route('contact.submit'));
    const csrf = @json(csrf_token());
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

    function captureTracking() {
      const params = new URLSearchParams(window.location.search);
      trackingParams.forEach(function (param) {
        const value = params.get(param);
        if (value) storageSet('lead_param_' + param, value);
      });
      if (!storageGet('lead_param_utm_source')) {
        storageSet('lead_param_utm_source', '(direct)');
        storageSet('lead_param_utm_medium', '(none)');
      }
      if (!storageGet('lead_param_landing_page')) {
        storageSet('lead_param_landing_page', window.location.pathname);
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

      const payload = {
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
        landing_page: firstValue(['landing_page']) || storageGet('lead_param_landing_page') || pagePath,
        referrer: document.referrer,
        geo_location: geoLocation,
      };
      trackingParams.forEach(function (param) {
        payload[param] = firstValue([param]) || storageGet('lead_param_' + param);
      });
      return payload;
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

    function showState(form, ok) {
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
          status.textContent = ok
            ? 'Thank you! Your submission has been received!'
            : 'Oops! Something went wrong while submitting the form.';
        }
        if (ok) form.hidden = true;
        return;
      }
      const done = wrapper.querySelector('.w-form-done');
      const fail = wrapper.querySelector('.w-form-fail');
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

      return hasField(['Name', 'full_name', 'name'])
        && hasField(['Email', 'email'])
        && hasField(['Phone', 'phone']);
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
        // Laravel first (spam gate). Google sheet + conversion only for clean leads.
        const res = await fetch(endpoint, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: JSON.stringify(payload),
        });
        if (!res.ok) {
          throw new Error('Lead submit failed');
        }
        const data = await res.json().catch(function () { return { ok: true }; });
        const isSpam = !!(data && data.spam);
        if (!isSpam) {
          postToGoogleBridges(payload);
          if (typeof window.gtag_report_conversion === 'function') {
            window.gtag_report_conversion();
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

@once
<script>
  (function () {
    const endpoint = @json(route('contact.submit'));
    const csrf = @json(csrf_token());
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

      const payload = {
        Name: firstValue(['Name', 'full_name', 'name']),
        Email: firstValue(['Email', 'email']),
        Phone: firstValue(['Phone', 'phone']),
        Subject: firstValue(['Subject', 'city', 'City', 'Company']),
        Message: firstValue(['Message', 'message', 'Description', 'description']),
        page_url: window.location.href,
        landing_page: firstValue(['landing_page']) || storageGet('lead_param_landing_page'),
        referrer: document.referrer,
        geo_location: geoLocation,
      };
      trackingParams.forEach(function (param) {
        payload[param] = firstValue([param]) || storageGet('lead_param_' + param);
      });
      return payload;
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

    async function submitLead(form) {
      const submitBtn = form.querySelector('input[type="submit"], button[type="submit"]');
      if (submitBtn) submitBtn.disabled = true;
      try {
        const payload = toPayload(form, await getGeoLocation());
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
        showState(form, true);
        if (typeof window.gtag_report_conversion === 'function') {
          window.gtag_report_conversion();
        }
      } catch (_) {
        showState(form, false);
      } finally {
        if (submitBtn) submitBtn.disabled = false;
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


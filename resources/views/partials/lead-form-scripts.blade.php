<script>
  (function () {
    const endpoint = @json(route('contact.submit'));
    const csrf = @json(csrf_token());
    const formSelectors = [
      'form#wf-form-Main-Form',
      'form#wf-form-Property-Form',
      'form#wf-form-Contact-V1-Form',
      'form#email-form-2'
    ];

    function toPayload(form) {
      const fd = new FormData(form);
      const payload = {
        Name: fd.get('Name') || '',
        Email: fd.get('Email') || '',
        Phone: fd.get('Phone') || '',
        Subject: fd.get('Subject') || '',
        Message: fd.get('Message') || '',
        page_url: window.location.href,
        utm_source: fd.get('utm_source') || localStorage.getItem('lead_param_utm_source') || '',
        utm_medium: fd.get('utm_medium') || localStorage.getItem('lead_param_utm_medium') || '',
        utm_campaign: fd.get('utm_campaign') || localStorage.getItem('lead_param_utm_campaign') || '',
      };
      return payload;
    }

    function showState(form, ok) {
      const wrapper = form.closest('.w-form');
      if (!wrapper) return;
      const done = wrapper.querySelector('.w-form-done');
      const fail = wrapper.querySelector('.w-form-fail');
      if (done) done.style.display = ok ? 'block' : 'none';
      if (fail) fail.style.display = ok ? 'none' : 'block';
      if (ok) form.style.display = 'none';
    }

    async function submitLead(form) {
      const payload = toPayload(form);
      const submitBtn = form.querySelector('input[type="submit"], button[type="submit"]');
      if (submitBtn) submitBtn.disabled = true;
      try {
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
      }
    }

    function bindForms() {
      const forms = formSelectors.flatMap((selector) => Array.from(document.querySelectorAll(selector)));
      forms.forEach((form) => {
        if (form.dataset.laravelLeadBound === '1') return;
        form.dataset.laravelLeadBound = '1';
        form.addEventListener('submit', function (e) {
          e.preventDefault();
          submitLead(form);
        });
      });
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', bindForms, { once: true });
    } else {
      bindForms();
    }
  })();
</script>


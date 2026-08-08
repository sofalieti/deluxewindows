@once
<script>
  (function () {
    const endpoint = @json(route('visit.store'));
    const csrfFallback = @json(csrf_token());

    function csrfHeaders() {
      const headers = {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      };
      const meta = document.querySelector('meta[name="csrf-token"]');
      const token = (meta && meta.getAttribute('content')) || csrfFallback || '';
      if (token) headers['X-CSRF-TOKEN'] = token;
      return headers;
    }

    function sendVisit() {
      if (!window.DeluxeAttribution) return;

      const captured = window.__dwAttributionCapture || window.DeluxeAttribution.capture();
      if (!captured || !captured.isNewSiteVisit) return;

      const payload = window.DeluxeAttribution.payloadFields();
      payload.page_url = window.location.href;

      try {
        fetch(endpoint, {
          method: 'POST',
          headers: csrfHeaders(),
          body: JSON.stringify(payload),
          credentials: 'same-origin',
          keepalive: true,
        }).catch(function () {
          // Tracking must never break the page.
        });
      } catch (_) {
        // Ignore.
      }
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', sendVisit);
    } else {
      sendVisit();
    }
  })();
</script>
@endonce

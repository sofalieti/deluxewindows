@once
@php
    $clarityId = trim((string) config('services.clarity.project_id'));
    $clarityReady = $clarityId !== '' && preg_match('/^[a-zA-Z0-9]+$/', $clarityId) === 1;
@endphp
<script>
  window.DeluxeClarity = window.DeluxeClarity || {
    sync: function () {
      if (typeof window.clarity !== 'function' || !window.DeluxeAttribution) return;
      var payload = window.DeluxeAttribution.payloadFields();
      if (payload.visitor_id) {
        window.clarity('identify', payload.visitor_id, payload.session_id || undefined);
      }
      var source = String(payload.utm_source || '').toLowerCase();
      var medium = String(payload.utm_medium || '').toLowerCase();
      var traffic = 'other';
      if (payload.gclid || payload.gbraid || payload.wbraid) traffic = 'google_ads';
      else if (payload.msclkid) traffic = 'microsoft_ads';
      else if (payload.fbclid) traffic = 'meta_ads';
      else if (medium === 'organic') traffic = 'organic';
      else if (medium === 'referral') traffic = 'referral';
      else if (source === '(direct)' || source === '') traffic = 'direct';
      function tag(key, value) {
        window.clarity('set', key, String(value || '').slice(0, 255));
      }
      tag('traffic_source', traffic);
      tag('utm_source', payload.utm_source || '');
      tag('utm_campaign', payload.utm_campaign || '');
      tag('utm_city', payload.utm_city || '');
      tag('has_gclid', payload.gclid ? '1' : '0');
      tag('has_msclkid', payload.msclkid ? '1' : '0');
    },
    event: function (name) {
      if (typeof window.clarity !== 'function') return;
      var safe = String(name || '').slice(0, 120);
      if (!safe) return;
      window.clarity('event', safe);
      window.clarity('upgrade', safe);
    }
  };
</script>
@if($clarityReady)
<script>
  (function (c, l, a, r, i, t, y) {
    c[a] = c[a] || function () { (c[a].q = c[a].q || []).push(arguments); };
    t = l.createElement(r);
    t.async = 1;
    t.src = 'https://www.clarity.ms/tag/' + i;
    y = l.getElementsByTagName(r)[0];
    y.parentNode.insertBefore(t, y);
  })(window, document, 'clarity', 'script', @json($clarityId));
</script>
<script>
  (function () {
    function shouldMask(el) {
      if (!el || !el.getAttribute) return false;
      var type = (el.getAttribute('type') || '').toLowerCase();
      var auto = (el.getAttribute('autocomplete') || '').toLowerCase();
      var name = (el.getAttribute('name') || '').toLowerCase();
      if (type === 'tel' || auto === 'tel' || auto === 'street-address' || auto === 'address-level1' || auto === 'address-level2' || auto === 'postal-code') {
        return true;
      }
      return name === 'phone' || name === 'city' || name === 'address' || name === 'zip' || name === 'postal' || name === 'subject';
    }

    function maskSensitive(root) {
      var nodes = (root || document).querySelectorAll('input, textarea');
      for (var i = 0; i < nodes.length; i++) {
        if (shouldMask(nodes[i])) nodes[i].setAttribute('data-clarity-mask', 'true');
      }
    }

    function start() {
      maskSensitive(document);
      if (window.DeluxeClarity) window.DeluxeClarity.sync();
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', start);
    } else {
      start();
    }

    document.addEventListener('focusin', function (e) {
      if (shouldMask(e.target)) e.target.setAttribute('data-clarity-mask', 'true');
    }, true);
  })();
</script>
@endif
@endonce

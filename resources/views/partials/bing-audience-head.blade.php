{{--
  Marks Microsoft Ads (Bing) visitors with html.dw-bing before first paint so the
  Bing-only offer copy (.bing-only / .bing-hide) never flashes for anyone else.
  A Google Ads click on the current URL or in the stored last touch always wins:
  those visitors keep the regular pages. ?audience=bing previews the Bing view for
  this tab without touching attribution; ?audience=default clears the preview.
--}}
<script>
  (function () {
    var html = document.documentElement;
    var PAID = /(?:^|[_\-\s])(cpc|ppc|paid|paidsearch|sem)(?:$|[_\-\s])/;
    var PREVIEW_KEY = 'dw_audience_preview';

    function stored(key) {
      try {
        return String(localStorage.getItem(key) || '');
      } catch (_) {
        return '';
      }
    }

    function isBingSource(source) {
      source = String(source || '').toLowerCase();
      return source === 'msn' || source.indexOf('bing') !== -1 || source.indexOf('microsoft') !== -1;
    }

    function isPaid(medium) {
      return PAID.test(String(medium || '').toLowerCase());
    }

    var query;
    try {
      query = new URLSearchParams(window.location.search);
    } catch (_) {
      return;
    }

    var preview = String(query.get('audience') || '').toLowerCase();
    try {
      if (preview === 'bing') sessionStorage.setItem(PREVIEW_KEY, 'bing');
      if (preview === 'default') sessionStorage.removeItem(PREVIEW_KEY);
      preview = sessionStorage.getItem(PREVIEW_KEY) || '';
    } catch (_) {
      preview = preview === 'bing' ? 'bing' : '';
    }

    var bing;
    if (query.get('gclid') || query.get('gbraid') || query.get('wbraid')) {
      bing = false;
    } else if (query.get('msclkid')) {
      bing = true;
    } else if (query.get('utm_source')) {
      bing = isBingSource(query.get('utm_source')) && isPaid(query.get('utm_medium'));
    } else if (stored('lead_param_gclid') || stored('lead_param_gbraid') || stored('lead_param_wbraid')) {
      bing = false;
    } else if (stored('lead_param_msclkid') || stored('lead_param_first_msclkid')) {
      bing = true;
    } else {
      var source = stored('lead_param_utm_source') || stored('lead_param_first_utm_source');
      var medium = stored('lead_param_utm_medium') || stored('lead_param_first_utm_medium');
      bing = isBingSource(source) && isPaid(medium);
    }

    if (preview === 'bing') bing = true;

    window.DeluxeAudience = { bing: bing };
    if (bing) html.classList.add('dw-bing');

    if (!bing) return;

    var LANDING_KEY = 'dw_bing_landing_path';
    var path = window.location.pathname || '/';
    if (path.length > 1 && path.charAt(path.length - 1) === '/') path = path.slice(0, -1);
    if (!path) path = '/';

    var landing = '';
    try {
      landing = sessionStorage.getItem(LANDING_KEY) || '';
      if (!landing) {
        sessionStorage.setItem(LANDING_KEY, path);
        landing = path;
      }
    } catch (_) {
      landing = path;
    }

    var isHome = path === '/';
    if (!isHome && path !== landing) html.classList.add('dw-bing-hide-hero');
  })();
</script>

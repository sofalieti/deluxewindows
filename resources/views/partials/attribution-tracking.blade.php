@once
<script>
  (function () {
    if (window.DeluxeAttribution) return;

    const TRACKING_PARAMS = [
      'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term',
      'matchtype', 'device', 'creative', 'gclid', 'fbclid', 'msclkid'
    ];
    // Carried with every touch but never treated as a campaign signal, so a bare
    // ?utm_city= cannot overwrite a real source or fake one for direct traffic.
    const CONTEXT_PARAMS = ['utm_city', 'utm_redirect'];
    const STORED_PARAMS = TRACKING_PARAMS.concat(CONTEXT_PARAMS);
    const SESSION_GAP_MS = 30 * 60 * 1000;
    const SESSION_FLAG = 'dw_site_visit';
    const ACTIVITY_KEY = 'dw_last_activity_at';

    function storageGet(key) {
      try {
        return localStorage.getItem(key) || '';
      } catch (_) {
        return '';
      }
    }

    function storageSet(key, value) {
      try {
        if (value === null || value === undefined) return;
        localStorage.setItem(key, String(value));
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

    function externalReferrer() {
      if (!document.referrer) return '';
      try {
        const referrerUrl = new URL(document.referrer);
        if (referrerUrl.hostname === window.location.hostname) return '';
        return document.referrer;
      } catch (_) {
        return '';
      }
    }

    function isNewSiteVisit() {
      let sessionFresh = false;
      try {
        sessionFresh = !sessionStorage.getItem(SESSION_FLAG);
      } catch (_) {
        sessionFresh = true;
      }

      const fromExternal = externalReferrer() !== '';

      let gapExpired = false;
      try {
        const last = parseInt(storageGet(ACTIVITY_KEY) || '0', 10) || 0;
        gapExpired = last > 0 && (Date.now() - last) > SESSION_GAP_MS;
      } catch (_) {
        gapExpired = false;
      }

      return sessionFresh || fromExternal || gapExpired;
    }

    function markVisitActivity() {
      try {
        sessionStorage.setItem(SESSION_FLAG, '1');
      } catch (_) {
        // sessionStorage may be blocked.
      }
      storageSet(ACTIVITY_KEY, String(Date.now()));
    }

    function inferFromReferrer(referrer) {
      if (!referrer) {
        return { utm_source: '(direct)', utm_medium: '(none)', utm_term: '' };
      }

      try {
        const referrerUrl = new URL(referrer);
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
          const keyword = referrerUrl.searchParams.get('q')
            || referrerUrl.searchParams.get('p')
            || referrerUrl.searchParams.get('query')
            || '(not provided)';
          return {
            utm_source: searchEngine,
            utm_medium: 'organic',
            utm_term: keyword,
          };
        }

        return { utm_source: host, utm_medium: 'referral', utm_term: '' };
      } catch (_) {
        return { utm_source: '(direct)', utm_medium: '(none)', utm_term: '' };
      }
    }

    function buildVisitSnapshot() {
      const params = new URLSearchParams(window.location.search);
      const snapshot = {
        landing_page: window.location.pathname || '/',
        referrer: externalReferrer(),
      };

      STORED_PARAMS.forEach(function (param) {
        snapshot[param] = params.get(param) || '';
      });

      if (!snapshot.creative) {
        snapshot.creative = params.get('utm_creative') || '';
      }

      // Google click-id cookies (_gcl_aw) can linger after a later Bing Ads visit.
      // Never backfill gclid onto a Bing/Microsoft (or Meta) paid hit — otherwise
      // last-touch keeps the old GCLID and we mis-label the visit as Google Ads.
      const msclkidInUrl = !!params.get('msclkid');
      const fbclidInUrl = !!params.get('fbclid');
      const sourceHint = String(snapshot.utm_source || '').toLowerCase();
      const mediumHint = String(snapshot.utm_medium || '').toLowerCase();
      const paidMedium = /(?:^|[_\-\s])(cpc|ppc|paid|paidsearch|paid_social|display|sem)(?:$|[_\-\s])/.test(mediumHint);
      const isBingPaidHit = msclkidInUrl || (
        paidMedium && (sourceHint === 'bing' || sourceHint === 'msn'
          || sourceHint.indexOf('bing') !== -1 || sourceHint.indexOf('microsoft') !== -1)
      );
      const isMetaPaidHit = fbclidInUrl || (
        paidMedium && (sourceHint === 'fb' || sourceHint === 'ig'
          || sourceHint.indexOf('facebook') !== -1 || sourceHint.indexOf('instagram') !== -1
          || sourceHint.indexOf('meta') !== -1)
      );

      if (!snapshot.gclid && !isBingPaidHit && !isMetaPaidHit) {
        snapshot.gclid = gclidFromGoogleCookies() || '';
      }
      if (isBingPaidHit || isMetaPaidHit) {
        snapshot.gclid = '';
      }

      const hasPaidOrCampaignSignal = TRACKING_PARAMS.some(function (param) {
        return !!snapshot[param];
      });

      if (!hasPaidOrCampaignSignal) {
        const inferred = inferFromReferrer(snapshot.referrer);
        snapshot.utm_source = inferred.utm_source;
        snapshot.utm_medium = inferred.utm_medium;
        if (inferred.utm_term) snapshot.utm_term = inferred.utm_term;
      } else if (!snapshot.utm_source) {
        snapshot.utm_source = snapshot.gclid ? 'google' : '(direct)';
        snapshot.utm_medium = snapshot.gclid ? 'cpc' : '(none)';
      }

      return snapshot;
    }

    function hasUrlTracking() {
      const params = new URLSearchParams(window.location.search);
      return TRACKING_PARAMS.some(function (param) {
        return !!params.get(param);
      }) || !!params.get('utm_creative');
    }

    function writeTouch(prefix, snapshot) {
      STORED_PARAMS.forEach(function (param) {
        storageSet(prefix + param, snapshot[param] || '');
      });
      storageSet(prefix + 'landing_page', snapshot.landing_page || '');
      storageSet(prefix + 'referrer', snapshot.referrer || '');
    }

    function readTouch(prefix) {
      const touch = {
        landing_page: storageGet(prefix + 'landing_page'),
        referrer: storageGet(prefix + 'referrer'),
      };
      STORED_PARAMS.forEach(function (param) {
        touch[param] = storageGet(prefix + param);
      });
      return touch;
    }

    function hasFirstTouch() {
      return !!storageGet('lead_param_first_utm_source')
        || !!storageGet('lead_param_first_gclid')
        || !!storageGet('lead_param_first_referrer')
        || !!storageGet('lead_param_first_landing_page');
    }

    function capture() {
      const snapshot = buildVisitSnapshot();
      const wasNewVisit = isNewSiteVisit();
      const shouldRefreshLast = wasNewVisit || hasUrlTracking();

      if (shouldRefreshLast) {
        writeTouch('lead_param_', snapshot);
      } else if (!storageGet('lead_param_utm_source') && !storageGet('lead_param_gclid')) {
        // First script run with empty storage mid-session (rare).
        writeTouch('lead_param_', snapshot);
      }

      if (!hasFirstTouch()) {
        writeTouch('lead_param_first_', readTouch('lead_param_'));
      }

      markVisitActivity();
      return {
        last: readTouch('lead_param_'),
        first: readTouch('lead_param_first_'),
        isNewSiteVisit: wasNewVisit,
      };
    }

    function payloadFields() {
      const last = readTouch('lead_param_');
      const first = readTouch('lead_param_first_');
      const payload = {};

      STORED_PARAMS.forEach(function (param) {
        payload[param] = last[param] || '';
        payload['first_' + param] = first[param] || '';
      });
      payload.landing_page = last.landing_page || '';
      payload.referrer = last.referrer || '';
      payload.first_landing_page = first.landing_page || '';
      payload.first_referrer = first.referrer || '';

      return payload;
    }

    window.DeluxeAttribution = {
      params: STORED_PARAMS,
      capture: capture,
      payloadFields: payloadFields,
      isNewSiteVisit: isNewSiteVisit,
      readLast: function () { return readTouch('lead_param_'); },
      readFirst: function () { return readTouch('lead_param_first_'); },
    };

    window.__dwAttributionCapture = capture();
  })();
</script>
@endonce

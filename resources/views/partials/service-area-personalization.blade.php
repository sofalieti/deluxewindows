@once
<script>
  (function () {
    // Visitors arriving from a geo targeted ad carry ?utm_city=.
    // Google fills {loc_physical_ms} (Google criteria id); Bing fills
    // {loc_physical} (Bing location id). The maps live in separate tables.
    var ENDPOINT = @json(route('service-area-phones'));
    var STORAGE_KEY = 'lead_param_utm_city';
    var CACHE_KEY = 'dw_service_area_phones_v4';

    function storageGet(key) {
      try {
        return localStorage.getItem(key) || '';
      } catch (_) {
        return '';
      }
    }

    function queryParam(name) {
      try {
        return new URLSearchParams(window.location.search).get(name) || '';
      } catch (_) {
        return '';
      }
    }

    function utmCity() {
      var fromUrl = queryParam('utm_city').trim();
      if (fromUrl) return fromUrl;
      return storageGet(STORAGE_KEY).trim();
    }

    function platformHint() {
      var source = (queryParam('utm_source') || storageGet('lead_param_utm_source') || '').toLowerCase();
      var msclkid = queryParam('msclkid') || storageGet('lead_param_msclkid') || '';
      var gclid = queryParam('gclid') || storageGet('lead_param_gclid') || '';

      if (msclkid) return 'bing';
      if (gclid) return 'google';
      if (
        source === 'bing' ||
        source === 'msn' ||
        source.indexOf('bing') !== -1 ||
        source.indexOf('microsoft') !== -1
      ) {
        return 'bing';
      }
      if (source === 'google' || source === 'adwords' || source.indexOf('google') !== -1) {
        return 'google';
      }
      return null;
    }

    function loadTable() {
      var cached = '';
      try {
        cached = sessionStorage.getItem(CACHE_KEY) || '';
      } catch (_) {
        cached = '';
      }

      if (cached) {
        try {
          return Promise.resolve(JSON.parse(cached));
        } catch (_) {
          // Fall through to a fresh fetch.
        }
      }

      return fetch(ENDPOINT, { credentials: 'same-origin' })
        .then(function (response) {
          return response.ok ? response.json() : null;
        })
        .then(function (data) {
          if (data) {
            try {
              sessionStorage.setItem(CACHE_KEY, JSON.stringify(data));
            } catch (_) {
              // Quota or private mode; the fetch is cached by the browser anyway.
            }
          }
          return data;
        })
        .catch(function () {
          return null;
        });
    }

    function slugify(value) {
      return String(value)
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
    }

    function geoMaps(table) {
      return {
        google: table.geo_google || table.geo || {},
        bing: table.geo_bing || {}
      };
    }

    function resolve(table, raw, platform) {
      if (!table || !raw) return null;

      if (!/^\d+$/.test(raw)) {
        var namedSlug = slugify(raw);
        var namedCity = (table.cities || {})[namedSlug];
        return namedCity ? { slug: namedSlug, city: namedCity } : null;
      }

      var maps = geoMaps(table);
      var order = platform === 'bing'
        ? ['bing', 'google']
        : platform === 'google'
          ? ['google', 'bing']
          : ['google', 'bing'];

      for (var i = 0; i < order.length; i++) {
        var slug = (maps[order[i]] || {})[raw];
        if (!slug) continue;
        var city = (table.cities || {})[slug];
        if (city) return { slug: slug, city: city };
      }

      return null;
    }

    function applyAreaLabel(name) {
      var labels = document.querySelectorAll('[data-area-label]');
      for (var i = 0; i < labels.length; i++) {
        labels[i].textContent = name;
      }
      var cityMarkers = document.querySelectorAll('h2[data-city]');
      for (var j = 0; j < cityMarkers.length; j++) {
        cityMarkers[j].setAttribute('data-city', name);
      }
    }

    function setText(selector, value) {
      var nodes = document.querySelectorAll(selector);
      for (var i = 0; i < nodes.length; i++) {
        nodes[i].textContent = value;
      }
    }

    function reveal(selector) {
      var nodes = document.querySelectorAll(selector);
      for (var i = 0; i < nodes.length; i++) {
        nodes[i].removeAttribute('hidden');
      }
    }

    function applyPhone(city) {
      // Single number spots (mobile hero promo, bottom CTA) swap in place.
      var links = document.querySelectorAll('[data-area-phone]');
      for (var i = 0; i < links.length; i++) {
        links[i].setAttribute('href', 'tel:' + city.phone_tel);
        if (links[i].hasAttribute('aria-label')) {
          links[i].setAttribute('aria-label', 'Call ' + city.phone_display);
        }
      }
      setText('[data-area-phone-number]', city.phone_display);

      // Desktop hero shows the general and the local number side by side.
      var locals = document.querySelectorAll('[data-area-phones-local]');
      for (var j = 0; j < locals.length; j++) {
        locals[j].setAttribute('href', 'tel:' + city.phone_tel);
        if (locals[j].hasAttribute('aria-label')) {
          locals[j].setAttribute('aria-label', 'Call ' + city.phone_display);
        }
      }
      setText('[data-area-phones-local-number]', city.phone_display);
      setText('[data-area-phones-local-label]', city.name);
      reveal('[data-area-phones-local]');
      reveal('[data-area-phones]');
    }

    function run() {
      var raw = utmCity();
      if (!raw) return;

      var platform = platformHint();

      loadTable().then(function (table) {
        var match = resolve(table, raw, platform);
        if (!match) return;

        applyAreaLabel(match.city.name);
        applyPhone(match.city);
      });
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', run);
    } else {
      run();
    }
  })();
</script>
@endonce

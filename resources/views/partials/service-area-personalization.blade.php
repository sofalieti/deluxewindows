@once
<script>
  (function () {
    // Visitors arriving from a geo targeted ad carry ?utm_city=. Google fills it
    // with {loc_physical_ms}, which is a numeric geo criteria id rather than a
    // city name, so the mapping has to happen here against our lookup table.
    var ENDPOINT = @json(route('service-area-phones'));
    var STORAGE_KEY = 'lead_param_utm_city';
    var CACHE_KEY = 'dw_service_area_phones';

    function storageGet(key) {
      try {
        return localStorage.getItem(key) || '';
      } catch (_) {
        return '';
      }
    }

    function utmCity() {
      try {
        var fromUrl = new URLSearchParams(window.location.search).get('utm_city');
        if (fromUrl) return fromUrl.trim();
      } catch (_) {
        // URLSearchParams is unavailable on very old browsers.
      }
      return storageGet(STORAGE_KEY).trim();
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

    function resolve(table, raw) {
      if (!table || !raw) return null;
      var slug = /^\d+$/.test(raw) ? (table.geo || {})[raw] : slugify(raw);
      if (!slug) return null;
      var city = (table.cities || {})[slug];
      return city ? { slug: slug, city: city } : null;
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

    function applyPhones(city) {
      var blocks = document.querySelectorAll('[data-area-phones]');
      for (var i = 0; i < blocks.length; i++) {
        blocks[i].removeAttribute('hidden');
      }

      var links = document.querySelectorAll('[data-area-phones-local]');
      for (var j = 0; j < links.length; j++) {
        links[j].setAttribute('href', 'tel:' + city.phone_tel);
        links[j].removeAttribute('hidden');
      }

      var numbers = document.querySelectorAll('[data-area-phones-local-number]');
      for (var k = 0; k < numbers.length; k++) {
        numbers[k].textContent = city.phone_display;
      }

      var labels = document.querySelectorAll('[data-area-phones-local-label]');
      for (var l = 0; l < labels.length; l++) {
        labels[l].textContent = city.name;
      }

      var blocksToShow = document.querySelectorAll('[data-area-phones-local-block]');
      for (var m = 0; m < blocksToShow.length; m++) {
        blocksToShow[m].removeAttribute('hidden');
      }
    }

    function run() {
      var raw = utmCity();
      if (!raw) return;

      loadTable().then(function (table) {
        var match = resolve(table, raw);
        if (!match) return;

        applyAreaLabel(match.city.name);
        applyPhones(match.city);
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

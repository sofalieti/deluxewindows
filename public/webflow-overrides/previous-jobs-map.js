/**
 * Previous jobs map — Leaflet + MarkerCluster (no Google Maps).
 */
(function () {
  const MAP_ID = "previous-jobs-map";
  const DATA_URL = "/data/previous-jobs.json";

  function waitForLibs(cb) {
    if (window.L && window.L.markerClusterGroup) {
      cb();
      return;
    }
    let tries = 0;
    const t = setInterval(function () {
      tries += 1;
      if (window.L && window.L.markerClusterGroup) {
        clearInterval(t);
        cb();
      } else if (tries > 80) {
        clearInterval(t);
        console.warn("[previous-jobs-map] Leaflet libs failed to load");
      }
    }, 100);
  }

  function buildPopup(p) {
    const title = p.label || [p.street, p.city].filter(Boolean).join(", ");
    let html = '<div class="jobs-map-popup"><strong>' + escapeHtml(title) + "</strong>";
    if (p.count && p.count > 1) {
      html += "<br>" + p.count + " projects at this address";
    }
    html += "</div>";
    return html;
  }

  function escapeHtml(s) {
    return String(s)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function initMap(points) {
    const el = document.getElementById(MAP_ID);
    if (!el || !points.length) return;

    const map = L.map(MAP_ID, {
      scrollWheelZoom: false,
      attributionControl: true,
    });

    L.tileLayer("https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png", {
      attribution:
        '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/attributions">CARTO</a>',
      subdomains: "abcd",
      maxZoom: 19,
    }).addTo(map);

    const cluster = L.markerClusterGroup({
      showCoverageOnHover: false,
      maxClusterRadius: 55,
      spiderfyOnMaxZoom: true,
      disableClusteringAtZoom: 17,
      iconCreateFunction: function (c) {
        const n = c.getChildCount();
        let size = "small";
        if (n >= 25) size = "large";
        else if (n >= 8) size = "medium";
        return L.divIcon({
          html: '<div><span>' + n + "</span></div>",
          className: "jobs-map-cluster jobs-map-cluster--" + size,
          iconSize: L.point(44, 44),
        });
      },
    });

    const pinIcon = L.divIcon({
      className: "jobs-map-pin",
      html: '<span class="jobs-map-pin__dot"></span>',
      iconSize: [18, 18],
      iconAnchor: [9, 9],
      popupAnchor: [0, -10],
    });

    const bounds = [];
    points.forEach(function (p) {
      if (typeof p.lat !== "number" || typeof p.lng !== "number") return;
      const m = L.marker([p.lat, p.lng], { icon: pinIcon });
      m.bindPopup(buildPopup(p));
      cluster.addLayer(m);
      bounds.push([p.lat, p.lng]);
    });

    map.addLayer(cluster);
    if (bounds.length) {
      map.fitBounds(bounds, { padding: [36, 36], maxZoom: 11 });
    } else {
      map.setView([37.7, -122.25], 9);
    }

    // Enable wheel zoom after click (avoids hijacking page scroll)
    map.on("click", function () {
      map.scrollWheelZoom.enable();
    });
    map.on("mouseout", function () {
      map.scrollWheelZoom.disable();
    });
  }

  function boot() {
    const el = document.getElementById(MAP_ID);
    if (!el) return;

    waitForLibs(function () {
      fetch(DATA_URL, { credentials: "same-origin" })
        .then(function (r) {
          if (!r.ok) throw new Error("HTTP " + r.status);
          return r.json();
        })
        .then(function (data) {
          const points = (data && data.points) || [];
          if (!points.length) {
            el.classList.add("jobs-map--empty");
            el.innerHTML =
              '<p class="jobs-map-empty">Map data is being prepared. Please check back shortly.</p>';
            return;
          }
          initMap(points);
        })
        .catch(function (err) {
          console.warn("[previous-jobs-map]", err);
          el.classList.add("jobs-map--empty");
          el.innerHTML =
            '<p class="jobs-map-empty">Unable to load map points right now.</p>';
        });
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot);
  } else {
    boot();
  }
})();

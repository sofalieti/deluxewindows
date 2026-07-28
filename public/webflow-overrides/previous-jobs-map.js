/**
 * Previous jobs map — Leaflet + MarkerCluster (lazy-loaded when near viewport).
 */
(function () {
  const MAP_ID = "previous-jobs-map";
  const DATA_URL = "/data/previous-jobs.json";
  const LEAFLET_CSS = "https://unpkg.com/leaflet@1.9.4/dist/leaflet.css";
  const CLUSTER_CSS = "https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css";
  const LEAFLET_JS = "https://unpkg.com/leaflet@1.9.4/dist/leaflet.js";
  const CLUSTER_JS = "https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js";

  let loading = false;
  let loaded = false;

  function loadCss(href) {
    return new Promise(function (resolve, reject) {
      if (document.querySelector('link[href="' + href + '"]')) {
        resolve();
        return;
      }
      const link = document.createElement("link");
      link.rel = "stylesheet";
      link.href = href;
      link.crossOrigin = "";
      link.onload = function () { resolve(); };
      link.onerror = function () { reject(new Error("CSS failed: " + href)); };
      document.head.appendChild(link);
    });
  }

  function loadScript(src) {
    return new Promise(function (resolve, reject) {
      if (document.querySelector('script[src="' + src + '"]')) {
        resolve();
        return;
      }
      const script = document.createElement("script");
      script.src = src;
      script.defer = true;
      script.crossOrigin = "";
      script.onload = function () { resolve(); };
      script.onerror = function () { reject(new Error("JS failed: " + src)); };
      document.body.appendChild(script);
    });
  }

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
        const label = n === 1
          ? "1 project location"
          : n + " project locations";
        return L.divIcon({
          html:
            "<div><span>" +
            n +
            '</span><span class="jobs-map-sr-only">' +
            label +
            "</span></div>",
          className: "jobs-map-cluster jobs-map-cluster--" + size,
          iconSize: L.point(44, 44),
        });
      },
    });

    const pinIcon = L.divIcon({
      className: "jobs-map-pin",
      html: '<span class="jobs-map-pin__dot" aria-hidden="true"></span>',
      iconSize: [18, 18],
      iconAnchor: [9, 9],
      popupAnchor: [0, -10],
    });

    const bounds = [];
    points.forEach(function (p) {
      if (typeof p.lat !== "number" || typeof p.lng !== "number") return;
      const name =
        p.label ||
        [p.street, p.city].filter(Boolean).join(", ") ||
        "Completed project";
      const m = L.marker([p.lat, p.lng], {
        icon: pinIcon,
        title: name,
        alt: name,
      });
      m.on("add", function () {
        const iconEl = m.getElement();
        if (iconEl) {
          iconEl.setAttribute("aria-label", "Project location: " + name);
        }
      });
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

    map.on("click", function () {
      map.scrollWheelZoom.enable();
    });
    map.on("mouseout", function () {
      map.scrollWheelZoom.disable();
    });
  }

  function showEmpty(el, message) {
    el.classList.add("jobs-map--empty");
    el.innerHTML = '<p class="jobs-map-empty">' + message + "</p>";
  }

  function bootMap() {
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
            showEmpty(el, "Map data is being prepared. Please check back shortly.");
            return;
          }
          initMap(points);
        })
        .catch(function (err) {
          console.warn("[previous-jobs-map]", err);
          showEmpty(el, "Unable to load map points right now.");
        });
    });
  }

  function loadDepsAndBoot() {
    if (loaded || loading) return;
    loading = true;

    Promise.all([
      loadCss(LEAFLET_CSS),
      loadCss(CLUSTER_CSS),
      loadScript(LEAFLET_JS).then(function () { return loadScript(CLUSTER_JS); }),
    ])
      .then(function () {
        loaded = true;
        bootMap();
      })
      .catch(function (err) {
        loading = false;
        console.warn("[previous-jobs-map]", err);
        const el = document.getElementById(MAP_ID);
        if (el) showEmpty(el, "Unable to load map right now.");
      });
  }

  function whenNearViewport(el, cb) {
    if (!("IntersectionObserver" in window)) {
      cb();
      return;
    }
    const io = new IntersectionObserver(
      function (entries) {
        if (!entries.some(function (e) { return e.isIntersecting; })) return;
        io.disconnect();
        cb();
      },
      { rootMargin: "400px 0px" }
    );
    io.observe(el);
  }

  function start() {
    const el = document.getElementById(MAP_ID);
    if (!el) return;
    whenNearViewport(el, loadDepsAndBoot);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", start);
  } else {
    start();
  }
})();

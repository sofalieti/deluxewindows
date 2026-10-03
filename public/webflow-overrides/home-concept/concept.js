/* Shared behaviour for /home-concept/* designs. Pure data-attribute driven, no framework. */
(function () {
  'use strict';
  var d = document;

  /* Header compact state */
  var header = d.querySelector('[data-hc-header]');
  if (header) {
    var onScroll = function () { header.classList.toggle('is-scrolled', window.scrollY > 24); };
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
  }

  /* Burger + drawer */
  var burger = d.querySelector('[data-hc-burger]');
  var drawer = d.querySelector('[data-hc-drawer]');
  function closeDrawer() {
    if (!burger || !drawer) return;
    burger.setAttribute('aria-expanded', 'false');
    drawer.hidden = true;
    d.documentElement.classList.remove('hc-lock');
  }
  if (burger && drawer) {
    burger.addEventListener('click', function () {
      var open = burger.getAttribute('aria-expanded') === 'true';
      if (open) { closeDrawer(); return; }
      burger.setAttribute('aria-expanded', 'true');
      drawer.hidden = false;
      d.documentElement.classList.add('hc-lock');
    });
    d.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeDrawer(); });
  }

  /* Quote links: smooth scroll + focus the first field */
  d.querySelectorAll('[data-hc-quote]').forEach(function (a) {
    a.addEventListener('click', function (e) {
      var t = d.getElementById('quote');
      if (!t) return;
      e.preventDefault();
      closeDrawer();
      t.scrollIntoView({ behavior: 'smooth', block: 'center' });
      var f = t.querySelector('input[name="Name"]');
      if (f) setTimeout(function () { f.focus({ preventScroll: true }); }, 500);
    });
  });

  /* Before / after */
  d.querySelectorAll('[data-hc-compare]').forEach(function (cmp) {
    var stage = cmp.querySelector('.hc-compare__stage');
    var before = cmp.querySelector('[data-hc-before]');
    var bImg = cmp.querySelector('[data-hc-before-img]');
    var line = cmp.querySelector('[data-hc-line]');
    var range = cmp.querySelector('.hc-compare__range');
    if (!stage || !before || !bImg || !line || !range) return;
    var drag = false;
    var sync = function () { bImg.style.width = stage.offsetWidth + 'px'; bImg.style.height = stage.offsetHeight + 'px'; };
    var set = function (p) {
      p = Math.max(0, Math.min(100, p));
      before.style.width = p + '%';
      line.style.left = p + '%';
      range.value = String(Math.round(p));
    };
    var fromX = function (x) { var r = stage.getBoundingClientRect(); if (r.width) set(((x - r.left) / r.width) * 100); };
    range.addEventListener('input', function () { set(Number(range.value)); });
    stage.addEventListener('pointerdown', function (e) { drag = true; stage.setPointerCapture(e.pointerId); fromX(e.clientX); });
    stage.addEventListener('pointermove', function (e) { if (drag) fromX(e.clientX); });
    ['pointerup', 'pointercancel'].forEach(function (ev) { stage.addEventListener(ev, function () { drag = false; }); });
    set(50);
    sync();
    if ('ResizeObserver' in window) new ResizeObserver(sync).observe(stage);
    else window.addEventListener('resize', sync);
    bImg.addEventListener('load', sync);
  });

  /* Horizontal rails */
  d.querySelectorAll('[data-hc-rail]').forEach(function (rail) {
    var id = rail.getAttribute('data-hc-rail');
    var step = function () { var c = rail.firstElementChild; return c ? c.getBoundingClientRect().width + 20 : 320; };
    d.querySelectorAll('[data-hc-rail-prev="' + id + '"]').forEach(function (b) { b.addEventListener('click', function () { rail.scrollBy({ left: -step(), behavior: 'smooth' }); }); });
    d.querySelectorAll('[data-hc-rail-next="' + id + '"]').forEach(function (b) { b.addEventListener('click', function () { rail.scrollBy({ left: step(), behavior: 'smooth' }); }); });
  });

  /* Hover matrix → preview image */
  d.querySelectorAll('[data-hc-matrix]').forEach(function (matrix) {
    var img = matrix.querySelector('[data-hc-preview-img]');
    var cap = matrix.querySelector('[data-hc-preview-cap]');
    if (!img) return;
    var fallbackSrc = img.getAttribute('src');
    var fallbackCap = cap ? cap.textContent : '';
    var rows = matrix.querySelectorAll('[data-hc-row]');
    rows.forEach(function (row) {
      var src = row.getAttribute('data-image');
      var name = row.getAttribute('data-name') || '';
      var activate = function () {
        rows.forEach(function (r) { r.classList.remove('is-active'); });
        row.classList.add('is-active');
        img.src = src || fallbackSrc;
        if (cap) cap.textContent = src ? name : fallbackCap;
      };
      row.addEventListener('mouseenter', activate);
      row.addEventListener('focus', activate);
    });
    matrix.addEventListener('mouseleave', function () {
      rows.forEach(function (r) { r.classList.remove('is-active'); });
      img.src = fallbackSrc;
      if (cap) cap.textContent = fallbackCap;
    });
  });

  /* Tabs */
  d.querySelectorAll('[data-hc-tabs]').forEach(function (root) {
    var tabs = root.querySelectorAll('[data-hc-tab]');
    var panels = root.querySelectorAll('[data-hc-panel]');
    function show(id) {
      tabs.forEach(function (t) { var on = t.getAttribute('data-hc-tab') === id; t.classList.toggle('is-active', on); t.setAttribute('aria-selected', on ? 'true' : 'false'); });
      panels.forEach(function (p) { p.hidden = p.getAttribute('data-hc-panel') !== id; });
    }
    tabs.forEach(function (t) { t.addEventListener('click', function () { show(t.getAttribute('data-hc-tab')); }); });
    if (tabs[0]) show(tabs[0].getAttribute('data-hc-tab'));
  });

  /* Section index highlight */
  d.querySelectorAll('[data-hc-index]').forEach(function (index) {
    if (!('IntersectionObserver' in window)) return;
    var links = {};
    index.querySelectorAll('a[href^="#"]').forEach(function (a) { links[a.getAttribute('href').slice(1)] = a; });
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (!en.isIntersecting) return;
        Object.keys(links).forEach(function (k) { links[k].classList.remove('is-active'); });
        var l = links[en.target.id];
        if (l) l.classList.add('is-active');
      });
    }, { rootMargin: '-40% 0px -55% 0px' });
    Object.keys(links).forEach(function (id) { var s = d.getElementById(id); if (s) io.observe(s); });
  });

  /* Reveal on scroll */
  if ('IntersectionObserver' in window) {
    var ro = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add('is-in'); ro.unobserve(en.target); } });
    }, { rootMargin: '0px 0px -10% 0px', threshold: 0.1 });
    d.querySelectorAll('[data-hc-reveal]').forEach(function (el) { ro.observe(el); });
  } else {
    d.querySelectorAll('[data-hc-reveal]').forEach(function (el) { el.classList.add('is-in'); });
  }

  /* Count-up numbers: <b data-hc-count="257">0</b> */
  var counters = d.querySelectorAll('[data-hc-count]');
  if (counters.length && 'IntersectionObserver' in window) {
    var co = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (!en.isIntersecting) return;
        var el = en.target, target = parseFloat(el.getAttribute('data-hc-count')) || 0;
        var dec = (String(el.getAttribute('data-hc-count')).split('.')[1] || '').length;
        var start = performance.now(), dur = 1200;
        (function tick(now) {
          var p = Math.min(1, (now - start) / dur), e = 1 - Math.pow(1 - p, 3);
          el.textContent = (target * e).toFixed(dec);
          if (p < 1) requestAnimationFrame(tick);
        })(start);
        co.unobserve(el);
      });
    }, { threshold: 0.6 });
    counters.forEach(function (el) { co.observe(el); });
  }
})();

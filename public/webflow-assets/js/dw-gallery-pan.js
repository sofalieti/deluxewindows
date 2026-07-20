/**
 * Vertical Ken Burns-style pan for gallery images that are taller than their frame.
 * Slows near top/bottom via ease-in-out timing (alternate).
 */
(function () {
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    return;
  }

  var gallery = document.getElementById('dw-gallery');
  if (!gallery) {
    return;
  }

  function isMobile() {
    return window.innerWidth <= 767;
  }

  function frameFor(img) {
    return img.closest('.dw-gallery__main, .dw-gallery__thumb');
  }

  function overflowRatio(img) {
    var frame = frameFor(img);
    if (!frame || !img.naturalWidth || !img.naturalHeight) {
      return 0;
    }
    var frameW = frame.clientWidth;
    var frameH = frame.clientHeight;
    if (frameW < 8 || frameH < 8) {
      return 0;
    }
    var fittedH = frameW * (img.naturalHeight / img.naturalWidth);
    return fittedH / frameH;
  }

  function shouldPan(img) {
    return overflowRatio(img) > 1.04;
  }

  function panDuration(img) {
    var ratio = overflowRatio(img);
    // ~1.5× faster than the first pass; taller crops still take a bit longer.
    var seconds = (10 + Math.min(14, (ratio - 1) * 12)) / 1.5;
    return Math.max(6, seconds).toFixed(1) + 's';
  }

  function stopPan(img) {
    img.classList.remove('dw-gallery-pan-y');
    img.style.removeProperty('--dw-gallery-pan-duration');
  }

  function startPan(img, force) {
    if (!shouldPan(img)) {
      stopPan(img);
      return;
    }
    var duration = panDuration(img);
    // Avoid restarting mid-pan (causes the initial jerk), unless forced (new src).
    if (
      !force &&
      img.classList.contains('dw-gallery-pan-y') &&
      img.style.getPropertyValue('--dw-gallery-pan-duration') === duration
    ) {
      return;
    }
    stopPan(img);
    img.style.setProperty('--dw-gallery-pan-duration', duration);
    void img.offsetWidth;
    img.classList.add('dw-gallery-pan-y');
  }

  function whenReady(img, fn) {
    if (img.complete && img.naturalWidth > 0) {
      fn();
      return;
    }
    img.addEventListener('load', fn, { once: true });
  }

  function refresh() {
    var mobile = isMobile();
    var main = document.getElementById('dw-main-img');

    if (main) {
      if (mobile) {
        stopPan(main);
      } else {
        whenReady(main, function () {
          startPan(main);
        });
      }
    }

    gallery.querySelectorAll('.dw-gallery__thumb img').forEach(function (img) {
      if (mobile) {
        whenReady(img, function () {
          startPan(img);
        });
      } else {
        stopPan(img);
      }
    });
  }

  var main = document.getElementById('dw-main-img');
  if (main) {
    var srcObserver = new MutationObserver(function () {
      whenReady(main, function () {
        if (!isMobile()) {
          startPan(main, true);
        }
      });
    });
    srcObserver.observe(main, { attributes: true, attributeFilter: ['src'] });
  }

  var resizeTimer = null;
  window.addEventListener('resize', function () {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(refresh, 150);
  });

  // After gallery fade swaps src / mobile carousel settles.
  document.addEventListener('visibilitychange', function () {
    if (!document.hidden) {
      refresh();
    }
  });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', refresh);
  } else {
    refresh();
  }

  // Re-check shortly after first paint (fonts/layout) and after thumb clicks.
  setTimeout(refresh, 400);
  gallery.addEventListener('click', function () {
    setTimeout(refresh, 220);
  });
})();

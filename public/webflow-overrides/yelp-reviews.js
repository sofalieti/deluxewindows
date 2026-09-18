(function () {
  if (window.__dwYelpReviewsInit) {
    return;
  }
  window.__dwYelpReviewsInit = true;

  function initBlock(root) {
    if (root.getAttribute('data-yelp-ready') === '1') {
      return;
    }
    root.setAttribute('data-yelp-ready', '1');

    var initial = parseInt(root.getAttribute('data-initial') || '8', 10);
    if (!Number.isFinite(initial) || initial < 1) {
      initial = 8;
    }

    var reviews = Array.prototype.slice.call(root.querySelectorAll('[data-yelp-review]'));
    var moreBtn = root.querySelector('[data-yelp-more]');
    var shown = 0;

    function reveal(count) {
      var next = Math.min(reviews.length, shown + count);
      for (var i = shown; i < next; i += 1) {
        reviews[i].hidden = false;
        setupClamp(reviews[i]);
      }
      shown = next;
      if (moreBtn) {
        if (shown < reviews.length) {
          moreBtn.classList.add('is-visible');
          moreBtn.hidden = false;
        } else {
          moreBtn.classList.remove('is-visible');
          moreBtn.hidden = true;
        }
      }
    }

    if (moreBtn) {
      moreBtn.addEventListener('click', function () {
        reveal(8);
      });
    }

    reviews.forEach(function (review, index) {
      review.hidden = index >= initial;
    });
    reveal(initial);

    root._yelpRefreshClamps = function () {
      reviews.forEach(function (review) {
        if (!review.hidden) {
          setupClamp(review);
        }
      });
    };
  }

  function setupClamp(review) {
    var text = review.querySelector('[data-yelp-text]');
    var btn = review.querySelector('[data-yelp-read-more]');
    var photos = review.querySelector('[data-yelp-photos]');
    if (!text || !btn || btn.getAttribute('data-ready') === '1') {
      return;
    }

    if (text.clientHeight === 0) {
      return;
    }

    btn.setAttribute('data-ready', '1');
    var overflowing = text.scrollHeight > text.clientHeight + 4;
    var hasPhotos = !!(photos && photos.children.length);
    if (!overflowing && !hasPhotos) {
      text.classList.remove('is-clamped');
      review.classList.remove('is-collapsed');
      return;
    }

    review.classList.add('is-collapsed');
    text.classList.add('is-clamped');
    btn.classList.add('is-visible');
    btn.hidden = false;
    btn.addEventListener('click', function () {
      review.classList.remove('is-collapsed');
      text.classList.remove('is-clamped');
      btn.classList.remove('is-visible');
      btn.hidden = true;
    });
  }

  function initDrawer() {
    var badge = document.querySelector('[data-yelp-badge]');
    var drawer = document.querySelector('[data-yelp-drawer]');
    var backdrop = document.querySelector('[data-yelp-drawer-backdrop]');
    var closeBtn = document.querySelector('[data-yelp-drawer-close]');
    if (!badge || !drawer || !backdrop) {
      return;
    }

    if (backdrop.parentNode !== document.body) {
      document.body.appendChild(backdrop);
    }
    if (drawer.parentNode !== document.body) {
      document.body.appendChild(drawer);
    }

    var lastFocus = null;

    function isOpen() {
      return drawer.classList.contains('is-open');
    }

    function openDrawer() {
      if (isOpen()) {
        return;
      }
      lastFocus = document.activeElement;
      drawer.classList.add('is-open');
      backdrop.classList.add('is-open');
      drawer.setAttribute('aria-hidden', 'false');
      badge.setAttribute('aria-expanded', 'true');
      document.body.classList.add('dw-yelp-drawer-open');
      drawer.scrollTop = 0;
      if (typeof drawer._yelpRefreshClamps === 'function') {
        drawer._yelpRefreshClamps();
      }
      window.requestAnimationFrame(function () {
        if (typeof drawer._yelpRefreshClamps === 'function') {
          drawer._yelpRefreshClamps();
        }
        if (closeBtn) {
          closeBtn.focus();
        } else {
          drawer.focus();
        }
      });
    }

    function closeDrawer() {
      if (!isOpen()) {
        return;
      }
      drawer.classList.remove('is-open');
      backdrop.classList.remove('is-open');
      drawer.setAttribute('aria-hidden', 'true');
      badge.setAttribute('aria-expanded', 'false');
      document.body.classList.remove('dw-yelp-drawer-open');
      if (lastFocus && typeof lastFocus.focus === 'function') {
        lastFocus.focus();
      } else {
        badge.focus();
      }
    }

    document.addEventListener(
      'click',
      function (event) {
        var target = event.target;
        if (!target || typeof target.closest !== 'function') {
          return;
        }
        if (target.closest('[data-yelp-badge]')) {
          event.preventDefault();
          event.stopPropagation();
          if (isOpen()) {
            closeDrawer();
          } else {
            openDrawer();
          }
          return;
        }
        if (target.closest('[data-yelp-drawer-close]')) {
          event.preventDefault();
          closeDrawer();
          return;
        }
        if (target.closest('[data-yelp-drawer-backdrop]')) {
          closeDrawer();
        }
      },
      true
    );

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && isOpen()) {
        event.preventDefault();
        closeDrawer();
      }
    });
  }

  function init() {
    document.querySelectorAll('[data-yelp-reviews]').forEach(initBlock);
    initDrawer();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

(function () {
  var CLAMP_LINES = 4;

  function initBlock(root) {
    var initial = parseInt(root.getAttribute('data-initial') || '6', 10);
    if (!Number.isFinite(initial) || initial < 1) {
      initial = 6;
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
  }

  function setupClamp(review) {
    var text = review.querySelector('[data-yelp-text]');
    var btn = review.querySelector('[data-yelp-read-more]');
    if (!text || !btn || btn.getAttribute('data-ready') === '1') {
      return;
    }

    btn.setAttribute('data-ready', '1');
    var overflowing = text.scrollHeight > text.clientHeight + 4;
    if (!overflowing) {
      text.classList.remove('is-clamped');
      return;
    }

    btn.classList.add('is-visible');
    btn.addEventListener('click', function () {
      text.classList.remove('is-clamped');
      btn.classList.remove('is-visible');
      btn.hidden = true;
    });
  }

  function init() {
    document.querySelectorAll('[data-yelp-reviews]').forEach(initBlock);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

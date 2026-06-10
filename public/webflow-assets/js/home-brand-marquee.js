(() => {
  const marquees = document.querySelectorAll('.brand-marquee');
  if (!marquees.length) return;

  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  marquees.forEach((root) => {
    const track = root.querySelector('.brand-marquee-track');
    if (!track || track.dataset.marqueeInit === '1') return;
    track.dataset.marqueeInit = '1';

    const items = [...track.children];
    if (!items.length) return;

    items.forEach((item) => {
      const clone = item.cloneNode(true);
      clone.setAttribute('aria-hidden', 'true');
      clone.querySelectorAll('a').forEach((link) => {
        link.setAttribute('tabindex', '-1');
      });
      track.appendChild(clone);
    });

    let userInteracting = false;
    let resumeTimer = null;
    let isDragging = false;
    let dragActive = false;
    let dragStartX = 0;
    let dragStartScroll = 0;
    const dragThreshold = 6;
    const speed = prefersReducedMotion ? 0 : 0.6;

    const halfWidth = () => track.scrollWidth / 2;

    const normalizeScroll = () => {
      const half = halfWidth();
      if (half <= 0) return;
      while (root.scrollLeft >= half) {
        root.scrollLeft -= half;
      }
      while (root.scrollLeft < 0) {
        root.scrollLeft += half;
      }
    };

    const pauseTemporarily = (ms = 2500) => {
      userInteracting = true;
      if (resumeTimer) clearTimeout(resumeTimer);
      resumeTimer = window.setTimeout(() => {
        userInteracting = false;
      }, ms);
    };

    const tick = () => {
      if (!userInteracting && speed > 0) {
        root.scrollLeft += speed;
        normalizeScroll();
      }
      window.requestAnimationFrame(tick);
    };

    root.addEventListener(
      'wheel',
      () => {
        pauseTemporarily();
        window.requestAnimationFrame(normalizeScroll);
      },
      { passive: true },
    );

    root.addEventListener(
      'scroll',
      () => {
        window.requestAnimationFrame(normalizeScroll);
      },
      { passive: true },
    );

    root.addEventListener(
      'touchstart',
      () => {
        userInteracting = true;
        if (resumeTimer) clearTimeout(resumeTimer);
      },
      { passive: true },
    );

    root.addEventListener(
      'touchend',
      () => {
        pauseTemporarily();
      },
      { passive: true },
    );

    root.addEventListener('pointerdown', (event) => {
      if (event.button !== 0) return;
      isDragging = true;
      dragActive = false;
      dragStartX = event.clientX;
      dragStartScroll = root.scrollLeft;
      if (resumeTimer) clearTimeout(resumeTimer);
      root.setPointerCapture(event.pointerId);
    });

    root.addEventListener('pointermove', (event) => {
      if (!isDragging) return;
      const delta = event.clientX - dragStartX;
      if (!dragActive && Math.abs(delta) < dragThreshold) return;
      dragActive = true;
      userInteracting = true;
      root.classList.add('is-dragging');
      root.scrollLeft = dragStartScroll - delta;
      normalizeScroll();
    });

    const endDrag = () => {
      isDragging = false;
      if (dragActive) {
        root.classList.remove('is-dragging');
        pauseTemporarily();
      }
      dragActive = false;
    };

    root.addEventListener('pointerup', endDrag);
    root.addEventListener('pointercancel', endDrag);

    window.requestAnimationFrame(tick);
  });
})();

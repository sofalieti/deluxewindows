(() => {
  const roots = document.querySelectorAll('.home-brand-logos');
  if (!roots.length) return;

  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  roots.forEach((root) => {
    const scroller = root.querySelector('.home-brand-logos__scroller');
    const track = root.querySelector('.home-brand-logos__track');
    if (!scroller || !track || track.dataset.marqueeInit === '1') return;
    track.dataset.marqueeInit = '1';

    const items = [...track.children];
    if (!items.length) return;

    const cloneTrack = track.cloneNode(true);
    cloneTrack.setAttribute('aria-hidden', 'true');
    cloneTrack.removeAttribute('data-marquee-init');
    cloneTrack.querySelectorAll('a').forEach((link) => link.setAttribute('tabindex', '-1'));
    track.after(cloneTrack);

    let userInteracting = false;
    let resumeTimer = null;
    let isDragging = false;
    let dragActive = false;
    let dragStartX = 0;
    let dragStartScroll = 0;
    const dragThreshold = 6;
    const speed = prefersReducedMotion ? 0 : 0.75;

    const loopWidth = () => track.getBoundingClientRect().width;

    const normalizeScroll = () => {
      const width = loopWidth();
      if (width <= 0) return;
      while (scroller.scrollLeft >= width) {
        scroller.scrollLeft -= width;
      }
      while (scroller.scrollLeft < 0) {
        scroller.scrollLeft += width;
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
        scroller.scrollLeft += speed;
        normalizeScroll();
      }
      window.requestAnimationFrame(tick);
    };

    scroller.addEventListener(
      'wheel',
      () => {
        pauseTemporarily();
        window.requestAnimationFrame(normalizeScroll);
      },
      { passive: true },
    );

    scroller.addEventListener(
      'scroll',
      () => {
        window.requestAnimationFrame(normalizeScroll);
      },
      { passive: true },
    );

    scroller.addEventListener(
      'touchstart',
      () => {
        userInteracting = true;
        if (resumeTimer) clearTimeout(resumeTimer);
      },
      { passive: true },
    );

    scroller.addEventListener(
      'touchend',
      () => {
        pauseTemporarily();
      },
      { passive: true },
    );

    scroller.addEventListener('pointerdown', (event) => {
      if (event.button !== 0) return;
      isDragging = true;
      dragActive = false;
      dragStartX = event.clientX;
      dragStartScroll = scroller.scrollLeft;
      if (resumeTimer) clearTimeout(resumeTimer);
      scroller.setPointerCapture(event.pointerId);
    });

    scroller.addEventListener('pointermove', (event) => {
      if (!isDragging) return;
      const delta = event.clientX - dragStartX;
      if (!dragActive && Math.abs(delta) < dragThreshold) return;
      dragActive = true;
      userInteracting = true;
      scroller.classList.add('is-dragging');
      scroller.scrollLeft = dragStartScroll - delta;
      normalizeScroll();
    });

    const endDrag = () => {
      isDragging = false;
      if (dragActive) {
        scroller.classList.remove('is-dragging');
        pauseTemporarily();
      }
      dragActive = false;
    };

    scroller.addEventListener('pointerup', endDrag);
    scroller.addEventListener('pointercancel', endDrag);

    window.requestAnimationFrame(tick);
  });
})();

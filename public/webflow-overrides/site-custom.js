/* Consolidated custom UI scripts (navbar/menu/modal/trust badges). */
(function () {
  function onReady(fn) {
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", fn, { once: true });
    } else {
      fn();
    }
  }

  // Mobile dropdown menu (simple panel under header).
  (function () {
    const NAVBAR = ".navbar-3";
    const STRIP = ".mobile-header-shell > .mobile-top-strip";
    const BTN = `${NAVBAR} .w-nav-button`;
    const MENU = `${NAVBAR} .nav-menu-wrapper-4.w-nav-menu`;
    const MOBILE = "(max-width: 991px)";
    const isMobile = () => window.matchMedia(MOBILE).matches;
    const isOpen = () => document.body.classList.contains("mobile-menu-open");

    const $ = (s) => document.querySelector(s);
    let barHeight = 0;
    let spacer = null;
    let scrollTicking = false;

    function ensureSpacer() {
      const bar = $(NAVBAR);
      if (!bar || spacer) return;
      spacer = document.createElement("div");
      spacer.className = "navbar-3-scroll-spacer";
      spacer.setAttribute("aria-hidden", "true");
      bar.insertAdjacentElement("afterend", spacer);
    }

    function measureBar() {
      const bar = $(NAVBAR);
      barHeight = bar ? Math.round(bar.getBoundingClientRect().height) : 0;
    }

    function shouldPinBar() {
      const strip = $(STRIP);
      if (!strip) return window.scrollY > 1;

      // Use the text strip position — fixed navbar always reports top: 0 in getBoundingClientRect
      return strip.getBoundingClientRect().bottom <= 0;
    }

    let applyingPin = false;

    function setPinnedState(bar, pinned) {
      // While the menu is open the bar must stay fixed in the viewport.
      if (isOpen()) {
        pinned = true;
      }

      applyingPin = true;
      bar.classList.toggle("navbar-3--pinned", pinned);
      bar.dataset.dwPinned = pinned ? "1" : "0";

      if (spacer) spacer.style.height = pinned ? `${barHeight}px` : "0px";
      applyingPin = false;
    }

    function updatePinnedBar() {
      if (!isMobile()) {
        const bar = $(NAVBAR);
        if (bar) setPinnedState(bar, false);
        return;
      }

      const bar = $(NAVBAR);
      if (!bar) return;
      ensureSpacer();
      measureBar();
      setPinnedState(bar, shouldPinBar() || isOpen());

      if (isOpen()) syncMenuPosition();
    }

    function onScrollPin() {
      if (scrollTicking) return;
      scrollTicking = true;
      requestAnimationFrame(() => {
        updatePinnedBar();
        scrollTicking = false;
      });
    }

    function syncMenuPosition() {
      const bar = $(NAVBAR);
      if (!bar) return;
      // Use bar height so top stays correct even if layout settles a frame later.
      const rect = bar.getBoundingClientRect();
      const bottom = Math.max(0, Math.round(rect.bottom));
      document.documentElement.style.setProperty("--mobile-nav-bottom", `${bottom}px`);
    }

    let scrollLockY = 0;

    function lockBodyScroll(lock) {
      if (lock) {
        scrollLockY = window.scrollY || window.pageYOffset || 0;
        document.body.style.position = "fixed";
        document.body.style.top = `-${scrollLockY}px`;
        document.body.style.left = "0";
        document.body.style.right = "0";
        document.body.style.width = "100%";
        return;
      }

      document.body.style.position = "";
      document.body.style.top = "";
      document.body.style.left = "";
      document.body.style.right = "";
      document.body.style.width = "";
      window.scrollTo(0, scrollLockY);
    }

    function setOpenState(shouldOpen) {
      const button = $(BTN);
      const menu = $(MENU);
      const bar = $(NAVBAR);

      document.body.classList.toggle("mobile-menu-open", shouldOpen);

      if (button) {
        button.classList.toggle("w--open", shouldOpen);
        button.setAttribute("aria-expanded", shouldOpen ? "true" : "false");
      }

      if (menu) {
        menu.classList.toggle("w--open", shouldOpen);
      }

      if (shouldOpen) {
        // Pin bar to viewport first, then place the panel under it.
        if (bar) {
          ensureSpacer();
          measureBar();
          setPinnedState(bar, true);
        }
        lockBodyScroll(true);
        requestAnimationFrame(() => {
          syncMenuPosition();
          requestAnimationFrame(syncMenuPosition);
        });
      } else {
        document.documentElement.style.removeProperty("--mobile-nav-bottom");
        lockBodyScroll(false);
        updatePinnedBar();
      }
    }

    function closeMenu() {
      setOpenState(false);
    }

    function toggleMenu() {
      if (!isMobile()) return;
      setOpenState(!isOpen());
    }

    document.addEventListener("click", (e) => {
      if (!isMobile()) return;

      if (e.target.closest(BTN)) {
        e.preventDefault();
        e.stopPropagation();
        toggleMenu();
        return;
      }

      if (isOpen() && !e.target.closest(MENU)) {
        closeMenu();
      }
    }, true);

    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && isOpen()) closeMenu();
    });

    window.addEventListener("resize", () => {
      measureBar();
      updatePinnedBar();
      if (!isMobile() && isOpen()) closeMenu();
      if (isOpen()) syncMenuPosition();
    }, { passive: true });

    window.addEventListener("scroll", onScrollPin, { passive: true });

    const pinObserver = new MutationObserver(() => {
      if (applyingPin || !isMobile()) return;
      const bar = $(NAVBAR);
      if (!bar) return;
      const shouldPin = shouldPinBar();
      if (shouldPin !== (bar.dataset.dwPinned === "1")) {
        updatePinnedBar();
      }
    });

    onReady(() => {
      measureBar();
      updatePinnedBar();
      const bar = $(NAVBAR);
      if (bar) {
        pinObserver.observe(bar, { attributes: true, attributeFilter: ["style", "class"] });
      }
    });

    window.addEventListener("load", () => {
      measureBar();
      updatePinnedBar();
    }, { passive: true });
  })();

  // Estimate modal open/close behavior.
  (function () {
    onReady(() => {
      const modal = document.getElementById("mobileEstimateModal");
      if (!modal) return;

      const openBtnSelector = "[data-open-estimate-modal]";
      const closeSelector = "[data-close-estimate-modal]";
      const form = modal.querySelector("form");
      const promoTarget = modal.querySelector("[data-estimate-modal-promo]");
      const defaultPromoHtml = promoTarget ? promoTarget.innerHTML : "";

      function syncModalPromo() {
        if (!promoTarget) return;

        const source = document.querySelector("#wf-form-Main-Form [data-estimate-form-promo]");
        const hasSpecificPagePromotion =
          source &&
          source.dataset.pagePromotion === "specific" &&
          source.innerHTML.trim();

        promoTarget.innerHTML = hasSpecificPagePromotion
          ? source.innerHTML
          : defaultPromoHtml;
      }

      function setOpenState(isOpen) {
        modal.classList.toggle("is-open", isOpen);
        modal.setAttribute("aria-hidden", isOpen ? "false" : "true");
        document.body.style.overflow = isOpen ? "hidden" : "";
      }

      function openModal() {
        syncModalPromo();
        setOpenState(true);
      }
      function closeModal() { setOpenState(false); }

      document.addEventListener("click", (e) => {
        const opener = e.target.closest(openBtnSelector);
        if (opener) {
          e.preventDefault();
          openModal();
          return;
        }
        if (e.target.closest(closeSelector)) {
          e.preventDefault();
          closeModal();
        }
      });

      document.addEventListener("keydown", (e) => {
        if (e.key === "Escape" && modal.classList.contains("is-open")) closeModal();
      });

      if (form) {
        form.addEventListener("submit", () => {
          setTimeout(() => {
            const done = modal.querySelector(".w-form-done");
            if (done && done.style.display === "block") closeModal();
          }, 350);
        });
      }
    });
  })();

  // Keep mobile menu button stable when Webflow mutates styles.
  (function () {
    const MOBILE = "(max-width: 991px)";

    function enforceMobileMenuButtonLock() {
      if (!window.matchMedia(MOBILE).matches) return;
      const btn = document.querySelector(".navbar-3 .menu-button.w-nav-button");
      if (!btn) return;
      btn.style.width = "44px";
      btn.style.height = "44px";
      btn.style.margin = "0";
      btn.style.padding = "0";
      btn.style.transform = "none";
      btn.style.left = "auto";
      btn.style.right = "auto";
      btn.style.background = "transparent";
      btn.style.display = "inline-flex";
      btn.style.alignItems = "center";
      btn.style.justifyContent = "center";
      const icon = btn.querySelector(".icon");
      if (icon) {
        icon.style.display = "flex";
        icon.style.alignItems = "center";
        icon.style.justifyContent = "center";
        icon.style.width = "100%";
        icon.style.height = "100%";
        icon.style.margin = "0";
        icon.style.padding = "0";
        icon.style.lineHeight = "1";
        icon.style.position = "static";
        icon.style.color = "#0f172a";
        icon.style.transform = "none";
      }
    }

    function apply() {
      enforceMobileMenuButtonLock();
    }

    onReady(apply);
    window.addEventListener("resize", apply, { passive: true });

    const mo = new MutationObserver(() => apply());
    mo.observe(document.documentElement, {
      subtree: true,
      childList: true,
      attributes: true,
      attributeFilter: ["class", "style"],
    });
  })();

  // Hero parallax background effect (desktop only).
  (function () {
    var isDesktop = window.matchMedia("(hover: hover) and (pointer: fine) and (min-width: 992px)");
    var ticking = false;

    function updateParallax() {
      ticking = false;
      if (!isDesktop.matches) return;
      var bg = document.querySelector(".div-block-59 .div-block-61[data-parallax]");
      if (!bg) return;
      var hero = bg.closest(".div-block-59");
      if (!hero) return;
      var rect = hero.getBoundingClientRect();
      if (rect.bottom < 0 || rect.top > window.innerHeight) return;
      // Move background at 25% of scroll speed → smooth slow lag
      var offset = -rect.top * 0.25;
      bg.style.transform = "translateY(" + offset + "px)";
    }

    function onScroll() {
      if (!ticking) {
        requestAnimationFrame(updateParallax);
        ticking = true;
      }
    }

    function init() {
      var bg = document.querySelector(".div-block-59 .div-block-61[data-parallax]");
      if (!bg) return;
      window.addEventListener("scroll", onScroll, { passive: true });
      updateParallax();
    }

    onReady(init);
  })();

  // Mobile FAB: fade in after scrolling past the hero section.
  (function () {
    const MOBILE = "(max-width: 991px)";
    const FAB = ".mobile-fab-estimate";
    const HERO = ".div-block-59";
    let ticking = false;

    function shouldShowFab() {
      const hero = document.querySelector(HERO);
      if (hero) return hero.getBoundingClientRect().bottom <= 0;
      return window.scrollY > 80;
    }

    function updateFabVisibility() {
      ticking = false;
      const fab = document.querySelector(FAB);
      if (!fab) return;

      if (!window.matchMedia(MOBILE).matches) {
        fab.classList.remove("mobile-fab-estimate--visible");
        return;
      }

      fab.classList.toggle("mobile-fab-estimate--visible", shouldShowFab());
    }

    function onScroll() {
      if (ticking) return;
      ticking = true;
      requestAnimationFrame(updateFabVisibility);
    }

    onReady(() => {
      updateFabVisibility();
      window.addEventListener("scroll", onScroll, { passive: true });
      window.addEventListener("resize", updateFabVisibility, { passive: true });
    });
  })();
})();


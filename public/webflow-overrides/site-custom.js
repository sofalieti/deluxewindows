/* Consolidated custom UI scripts (navbar/menu/modal/trust badges). */
(function () {
  function onReady(fn) {
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", fn, { once: true });
    } else {
      fn();
    }
  }

  // Desktop dropdown toggles: disable click behavior, keep hover.
  (function () {
    const DESKTOP = "(hover: hover) and (pointer: fine) and (min-width: 992px)";
    const scopeToggles = ".list-nav-menu-2 .dropdown-wrapper.w-dropdown > .w-dropdown-toggle";

    function isDesktop() {
      return window.matchMedia(DESKTOP).matches;
    }

    function disableClickOnToggle(toggle) {
      if (toggle.dataset.dwDesktopBound === "1") return;
      toggle.dataset.dwDesktopBound = "1";

      const cancel = (e) => {
        if (!isDesktop()) return;
        e.preventDefault();
        e.stopImmediatePropagation();
      };

      toggle.addEventListener("pointerdown", cancel, true);
      toggle.addEventListener("mousedown", cancel, true);
      toggle.addEventListener("mouseup", cancel, true);
      toggle.addEventListener("click", cancel, true);
      toggle.addEventListener(
        "keydown",
        function (e) {
          if (!isDesktop()) return;
          if (e.key === "Enter" || e.key === " ") cancel(e);
        },
        true,
      );

      toggle.setAttribute("aria-disabled", "true");
    }

    function bind() {
      if (!isDesktop()) return;
      document.querySelectorAll(scopeToggles).forEach(disableClickOnToggle);
    }

    onReady(bind);
    window.addEventListener("resize", bind, { passive: true });
  })();

  // Mobile dropdown menu (simple panel under header).
  (function () {
    const NAVBAR = ".navbar-3";
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
      const bar = $(NAVBAR);
      if (!bar) return false;
      // Pin the logo/phone/menu row when it reaches the top — not the text strip above it
      return bar.getBoundingClientRect().top <= 0;
    }

    let applyingPin = false;

    function setPinnedState(bar, pinned) {
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
      setPinnedState(bar, shouldPinBar());

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
      const bottom = Math.round(bar.getBoundingClientRect().bottom);
      document.documentElement.style.setProperty("--mobile-nav-bottom", `${bottom}px`);
    }

    function setOpenState(shouldOpen) {
      const button = $(BTN);
      const menu = $(MENU);

      document.body.classList.toggle("mobile-menu-open", shouldOpen);

      if (button) {
        button.classList.toggle("w--open", shouldOpen);
        button.setAttribute("aria-expanded", shouldOpen ? "true" : "false");
      }

      if (menu) {
        menu.classList.toggle("w--open", shouldOpen);
      }

      if (shouldOpen) {
        syncMenuPosition();
      } else {
        document.documentElement.style.removeProperty("--mobile-nav-bottom");
      }
      updatePinnedBar();
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

      function setOpenState(isOpen) {
        modal.classList.toggle("is-open", isOpen);
        modal.setAttribute("aria-hidden", isOpen ? "false" : "true");
        document.body.style.overflow = isOpen ? "hidden" : "";
      }

      function openModal() { setOpenState(true); }
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
      const icon = btn.querySelector(".icon");
      if (icon) {
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

  // Link trust badges section background to hero background on mobile.
  (function () {
    function linkTrustBadgesToHeroBg() {
      if (!window.matchMedia("(max-width: 991px)").matches) return;
      const bar = document.getElementById("trustBadgesBar");
      if (!bar) return;
      const heroBgLayer = document.querySelector(".div-block-59 > .div-block-61");
      if (!heroBgLayer) return;
      const bgImage = window.getComputedStyle(heroBgLayer).backgroundImage;
      if (!bgImage || bgImage === "none") return;
      bar.style.setProperty("--trust-badges-bg-image", bgImage);
      bar.classList.add("trust-badges--hero-linked");
    }

    onReady(linkTrustBadgesToHeroBg);
    window.addEventListener("resize", linkTrustBadgesToHeroBg, { passive: true });
  })();
})();


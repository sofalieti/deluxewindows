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

  // Mobile menu placement and dimmer behavior.
  (function () {
    const NAVBAR = ".navbar-3";
    const NAV = `${NAVBAR} .navbar-container.w-nav`;
    const BTN = `${NAVBAR} .w-nav-button`;
    const MOBILE = "(max-width: 991px)";
    const isMobile = () => window.matchMedia(MOBILE).matches;

    let dimmer = document.getElementById("menuDimmer");
    if (!dimmer) {
      dimmer = document.createElement("div");
      dimmer.id = "menuDimmer";
      document.body.appendChild(dimmer);
    }

    const $ = (s, root = document) => root.querySelector(s);
    const btn = () => $(BTN);
    const navRoot = () => $(NAV);
    const overlayFromButton = () => {
      const b = btn();
      if (!b) return null;
      const overlayId = b.getAttribute("aria-controls");
      if (!overlayId) return null;
      return document.getElementById(overlayId);
    };
    const ov = () => overlayFromButton() || $(`${NAVBAR} .w-nav-overlay`);
    const menu = () => {
      const o = ov();
      if (o) {
        const inOverlay = o.querySelector(".w-nav-menu");
        if (inOverlay) return inOverlay;
      }
      return navRoot()?.querySelector(".w-nav-menu") || null;
    };
    const open = () => {
      const b = btn();
      if (!b) return false;
      return b.classList.contains("w--open") || b.getAttribute("aria-expanded") === "true";
    };

    const lockScroll = () => {
      document.documentElement.style.overflow = "hidden";
      document.body.style.overflow = "hidden";
    };
    const unlockScroll = () => {
      document.documentElement.style.overflow = "";
      document.body.style.overflow = "";
    };

    function navHeight() {
      const el = $(NAVBAR);
      const h = el ? Math.round(el.getBoundingClientRect().height) : 70;
      return h || 70;
    }

    function ensureMenuInOverlay() {
      const o = ov();
      const m = menu();
      if (!o || !m || m.parentNode === o) return;
      o.appendChild(m);
    }

    function placeUnderHeader() {
      const h = navHeight();
      const o = ov();
      if (o) {
        if (o.parentNode !== document.body) document.body.appendChild(o);
        Object.assign(o.style, {
          position: "fixed",
          top: h + "px",
          left: "0",
          right: "0",
          bottom: "0",
          width: "100%",
          overflow: "visible",
          display: "block",
          pointerEvents: "auto",
          zIndex: "1300",
        });
      }

      const m = menu();
      if (m) {
        Object.assign(m.style, {
          position: "fixed",
          top: h + "px",
          left: "0",
          right: "0",
          bottom: "0",
          overflowY: "auto",
          maxHeight: "none",
          WebkitOverflowScrolling: "touch",
          zIndex: "1301",
          paddingTop: "16px",
          display: "block",
        });
      }

      Object.assign(dimmer.style, {
        position: "fixed",
        top: h + "px",
        left: "0",
        right: "0",
        bottom: "0",
        zIndex: "1299",
      });
    }

    function show() {
      if (!isMobile()) return;
      ensureMenuInOverlay();
      document.body.classList.add("mobile-menu-open");
      placeUnderHeader();
      dimmer.style.opacity = "1";
      dimmer.style.pointerEvents = "auto";
      lockScroll();
    }

    function hide() {
      dimmer.style.opacity = "0";
      dimmer.style.pointerEvents = "none";
      document.body.classList.remove("mobile-menu-open");
      unlockScroll();
      const o = ov();
      if (o) {
        o.style.pointerEvents = "none";
        o.style.display = "none";
        o.style.height = "";
      }
      const m = menu();
      if (m) {
        m.style.paddingTop = "";
        if (!open()) m.style.display = "";
      }
    }

    function sync() {
      if (!isMobile()) return hide();
      if (open()) show();
      else hide();
    }

    function forceOpen() {
      const b = btn();
      const o = ov();
      const m = menu();
      if (b) {
        b.classList.add("w--open");
        b.setAttribute("aria-expanded", "true");
      }
      if (m) {
        m.classList.add("w--open");
        m.style.display = "block";
      }
      if (o) {
        o.style.display = "block";
        o.style.pointerEvents = "auto";
      }
      show();
    }

    function forceClose() {
      const b = btn();
      const o = ov();
      const m = menu();
      if (b) {
        b.classList.remove("w--open");
        b.setAttribute("aria-expanded", "false");
      }
      if (m) {
        m.classList.remove("w--open");
        m.style.display = "none";
      }
      if (o) {
        o.style.display = "none";
        o.style.pointerEvents = "none";
      }
      hide();
    }

    document.addEventListener("click", (e) => {
      const trigger = e.target.closest(BTN);
      if (!trigger || !isMobile()) return;
      e.preventDefault();
      e.stopPropagation();
      if (open()) forceClose();
      else forceOpen();
    }, true);

    dimmer.addEventListener("click", () => {
      if (open()) forceClose();
    });

    const mo = new MutationObserver(() => setTimeout(sync, 0));
    mo.observe(document.documentElement, {
      subtree: true,
      childList: true,
      attributes: true,
      attributeFilter: ["class", "style", "data-nav-menu-open"],
    });

    window.addEventListener("resize", () => {
      if (open()) placeUnderHeader();
      sync();
    }, { passive: true });

    window.addEventListener("scroll", () => {
      if (open()) placeUnderHeader();
    }, { passive: true });

    onReady(sync);
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


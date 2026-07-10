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
      const promoTarget = modal.querySelector("[data-estimate-modal-promo]");
      const defaultPromoHtml = promoTarget ? promoTarget.innerHTML : "";

      function syncModalPromo() {
        if (!promoTarget) return;

        const source = document.querySelector("#wf-form-Main-Form [data-estimate-form-promo]");
        promoTarget.innerHTML =
          source && source.innerHTML.trim() ? source.innerHTML : defaultPromoHtml;
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

  // Brand "All collections" sidebar — mobile dropdown (bottom-left FAB).
  (function () {
    const MOBILE = "(max-width: 991px)";

    function applyScrollBlock(list) {
      const sb = list.querySelector(".scroll-block");
      if (!sb) return;
      const listRect = list.getBoundingClientRect();
      const sbTop = sb.getBoundingClientRect().top - listRect.top;
      const pad = 16;
      const available = listRect.height - sbTop - pad;
      sb.style.maxHeight = Math.max(120, available) + "px";
    }

    function bindSidebarDropdowns() {
      if (!window.matchMedia(MOBILE).matches) return;

      const toggles = document.querySelectorAll(
        '.section_sidebar .dropdown-tab.sidebar-dropdown [data-dd="toggle"]',
      );
      const lists = [];

      toggles.forEach((toggle) => {
        if (toggle.dataset.dwSidebarBound === "1") return;
        toggle.dataset.dwSidebarBound = "1";

        const list = toggle.parentElement.querySelector('[data-dd="list"]');
        if (!list) return;

        const sidebarIcon = toggle.querySelector(".sidebar-icon");

        if (!lists.includes(list)) {
          lists.push(list);
          list.style.overflow = "hidden";
          list.style.maxHeight = "0px";
          list.style.transition = "max-height 0.35s ease";
          list.dataset.open = "false";
          list.style.display = "none";
        }

        toggle.addEventListener("click", () => {
          const isOpen = list.dataset.open === "true";

          lists.forEach((other) => {
            if (other !== list && other.dataset.open === "true") {
              const otherToggle = other.parentElement.querySelector('[data-dd="toggle"]');
              const otherSidebarIcon = otherToggle?.querySelector(".sidebar-icon");
              other.style.overflow = "hidden";
              other.style.display = "block";
              other.style.maxHeight = other.scrollHeight + "px";
              requestAnimationFrame(() => {
                other.style.maxHeight = "0px";
              });
              other.dataset.open = "false";
              setTimeout(() => {
                if (other.dataset.open === "false") other.style.display = "none";
              }, 360);
              if (otherSidebarIcon) otherSidebarIcon.style.transform = "rotate(0deg)";
            }
          });

          if (isOpen) {
            list.style.overflow = "hidden";
            list.style.display = "block";
            list.style.maxHeight = list.scrollHeight + "px";
            requestAnimationFrame(() => {
              list.style.maxHeight = "0px";
            });
            list.dataset.open = "false";
            setTimeout(() => {
              if (list.dataset.open === "false") list.style.display = "none";
            }, 360);
            if (sidebarIcon) sidebarIcon.style.transform = "rotate(0deg)";
          } else {
            list.style.display = "block";
            list.style.overflow = "hidden";
            list.style.maxHeight = "0px";
            requestAnimationFrame(() => {
              list.style.maxHeight = list.scrollHeight + "px";
            });
            list.dataset.open = "true";
            if (sidebarIcon) {
              sidebarIcon.style.transition = "transform 0.35s ease";
              sidebarIcon.style.transform = "rotate(180deg)";
            }
            setTimeout(() => {
              if (list.dataset.open === "true") {
                list.style.maxHeight = "none";
                list.style.overflow = "auto";
                applyScrollBlock(list);
              }
            }, 360);
          }
        });
      });

      window.addEventListener(
        "resize",
        () => {
          lists.forEach((l) => {
            if (l.dataset.open === "true") requestAnimationFrame(() => applyScrollBlock(l));
          });
        },
        { passive: true },
      );
    }

    onReady(bindSidebarDropdowns);
    window.addEventListener("resize", bindSidebarDropdowns, { passive: true });
  })();
})();


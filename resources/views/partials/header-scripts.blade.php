        <div class="code-embed-3 w-embed w-script">
          <script>
            (function () {
              const DESKTOP = "(hover: hover) and (pointer: fine) and (min-width: 992px)";
              const scopeToggles = ".list-nav-menu-2 .dropdown-wrapper.w-dropdown > .w-dropdown-toggle";

              function isDesktop() {
                return window.matchMedia(DESKTOP).matches;
              }

              function disableClickOnToggle(toggle) {
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

              document.addEventListener("DOMContentLoaded", bind);
              window.addEventListener("resize", function () {
                bind();
              });
            })();
          </script>

          <script>
            (function () {
              const MOBILE = "(max-width: 991px)";
              const isMobile = () => window.matchMedia(MOBILE).matches;
              const BODY_OPEN_CLASS = "dw-mobile-menu-open";
              const TOGGLE_SELECTOR = ".dw-mobile-menu-toggle";
              const PANEL_SELECTOR = "#dw-mobile-menu-panel";
              const BACKDROP_SELECTOR = "[data-role='dw-mobile-menu-backdrop']";
              const LINK_SELECTOR = ".dw-mobile-menu-link, .dw-mobile-menu-cta a";

              const $ = (s, root = document) => root.querySelector(s);
              const toggleBtn = () => $(TOGGLE_SELECTOR);
              const panel = () => $(PANEL_SELECTOR);
              const backdrop = () => $(BACKDROP_SELECTOR);
              const isOpen = () => document.body.classList.contains(BODY_OPEN_CLASS);

              function lockScroll() {
                document.documentElement.style.overflow = "hidden";
                document.body.style.overflow = "hidden";
              }

              function unlockScroll() {
                document.documentElement.style.overflow = "";
                document.body.style.overflow = "";
              }

              function renderState(open) {
                const btn = toggleBtn();
                const p = panel();
                if (btn) btn.setAttribute("aria-expanded", open ? "true" : "false");
                if (p) p.setAttribute("aria-hidden", open ? "false" : "true");
              }

              function openMenu() {
                if (!isMobile()) return;
                document.body.classList.add(BODY_OPEN_CLASS);
                lockScroll();
                renderState(true);
              }

              function closeMenu() {
                document.body.classList.remove(BODY_OPEN_CLASS);
                unlockScroll();
                renderState(false);
              }

              function toggleMenu() {
                if (isOpen()) closeMenu();
                else openMenu();
              }

              document.addEventListener("click", (event) => {
                const t = event.target;
                if (!(t instanceof Element)) return;

                if (t.closest(TOGGLE_SELECTOR)) {
                  event.preventDefault();
                  toggleMenu();
                  return;
                }

                if (t.closest(BACKDROP_SELECTOR)) {
                  event.preventDefault();
                  closeMenu();
                  return;
                }

                if (t.closest(LINK_SELECTOR) && isOpen()) {
                  closeMenu();
                }
              });

              document.addEventListener("keydown", (event) => {
                if (event.key === "Escape" && isOpen()) {
                  closeMenu();
                }
              });

              window.addEventListener("resize", () => {
                if (!isMobile() && isOpen()) closeMenu();
              });

              document.addEventListener("DOMContentLoaded", () => {
                renderState(false);
              });
            })();
          </script>
        </div>
      </div>

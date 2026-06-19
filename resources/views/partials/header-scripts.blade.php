        @once
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
              const ov = () => overlayFromButton() || $(".w-nav-overlay", navRoot() || document);
              const menu = () => $(".w-nav-menu", navRoot() || document);
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
                    paddingTop: "0",
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
                }
              }

              function sync() {
                if (!isMobile()) {
                  hide();
                  return;
                }
                if (open()) show();
                else hide();
              }

              document.addEventListener("click", (e) => {
                if (e.target.closest(BTN)) {
                  setTimeout(sync, 0);
                }
              });

              dimmer.addEventListener("click", () => {
                const b = btn();
                if (b && (b.classList.contains("w--open") || b.getAttribute("aria-expanded") === "true")) b.click();
              });

              const mo = new MutationObserver(() => setTimeout(sync, 0));
              mo.observe(document.documentElement, {
                subtree: true,
                childList: true,
                attributes: true,
                attributeFilter: ["class", "style", "data-nav-menu-open"],
              });

              window.addEventListener(
                "resize",
                () => {
                  if (open()) placeUnderHeader();
                  sync();
                },
                { passive: true },
              );

              window.addEventListener(
                "scroll",
                () => {
                  if (open()) placeUnderHeader();
                },
                { passive: true },
              );

              document.addEventListener("DOMContentLoaded", sync);
            })();
          </script>

          <script>
            (function () {
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

              function openModal() {
                setOpenState(true);
              }

              function closeModal() {
                setOpenState(false);
              }

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
                if (e.key === "Escape" && modal.classList.contains("is-open")) {
                  closeModal();
                }
              });

              if (form) {
                form.addEventListener("submit", () => {
                  setTimeout(() => {
                    const done = modal.querySelector(".w-form-done");
                    if (done && done.style.display === "block") {
                      closeModal();
                    }
                  }, 350);
                });
              }

            })();
          </script>

          <script>
            (function () {
              const DESKTOP = "(min-width: 992px)";
              const MOBILE = "(max-width: 991px)";

              function enforceDesktopDropdownAlignment() {
                if (!window.matchMedia(DESKTOP).matches) return;
                document.querySelectorAll(".header-wrapper-2 .dropdown-toogle-2.w-dropdown-toggle").forEach((toggle) => {
                  toggle.style.display = "inline-flex";
                  toggle.style.alignItems = "center";
                  toggle.style.justifyContent = "flex-start";
                  toggle.style.columnGap = "6px";
                  toggle.style.minHeight = "56px";
                  toggle.style.paddingTop = "0";
                  toggle.style.paddingBottom = "0";

                  const first = toggle.firstElementChild;
                  if (first) {
                    first.style.display = "inline-flex";
                    first.style.alignItems = "center";
                    first.style.lineHeight = "1.1";
                    first.style.minHeight = "56px";
                  }

                  const arrow = toggle.querySelector(".dropdown-arrow");
                  if (arrow) {
                    arrow.style.display = "inline-flex";
                    arrow.style.alignItems = "center";
                    arrow.style.lineHeight = "1";
                  }
                });
              }

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
                enforceDesktopDropdownAlignment();
                enforceMobileMenuButtonLock();
              }

              if (document.readyState === "loading") {
                document.addEventListener("DOMContentLoaded", apply, { once: true });
              } else {
                apply();
              }

              window.addEventListener("resize", apply, { passive: true });

              const mo = new MutationObserver(() => apply());
              mo.observe(document.documentElement, {
                subtree: true,
                childList: true,
                attributes: true,
                attributeFilter: ["class", "style"],
              });
            })();
          </script>

          <script>
            (function () {
              function syncContainerDefault2Padding() {
                const source = document.querySelector(".container-default");
                if (!source) return;

                const cs = window.getComputedStyle(source);
                const left = cs.paddingLeft;
                const right = cs.paddingRight;
                if (!left || !right) return;

                document.querySelectorAll(".container-default-2").forEach((el) => {
                  el.style.paddingLeft = left;
                  el.style.paddingRight = right;
                });
              }

              if (document.readyState === "loading") {
                document.addEventListener("DOMContentLoaded", syncContainerDefault2Padding, { once: true });
              } else {
                syncContainerDefault2Padding();
              }

              window.addEventListener("resize", syncContainerDefault2Padding, { passive: true });
            })();
          </script>
        </div>
        @endonce
      </div>

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
                    top: "0",
                    left: "0",
                    right: "0",
                    bottom: "0",
                    width: "100%",
                    overflow: "visible",
                    display: "block",
                    pointerEvents: "auto",
                  });
                }

                const m = menu();
                if (m) {
                  Object.assign(m.style, {
                    position: "fixed",
                    top: "0",
                    left: "0",
                    right: "0",
                    bottom: "0",
                    overflowY: "auto",
                    maxHeight: "none",
                    WebkitOverflowScrolling: "touch",
                    paddingTop: h + "px",
                  });
                }

                dimmer.style.top = "0";
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
              const mobileMq = window.matchMedia("(max-width: 991px)");
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
                if (!mobileMq.matches) return;
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

              mobileMq.addEventListener("change", () => {
                if (!mobileMq.matches) closeModal();
              });
            })();
          </script>
        </div>
      </div>

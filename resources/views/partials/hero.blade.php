        <div class="code-embed-3 w-embed w-script">
          <script>
            (function () {
              / Десктопный ховер: не тач, есть hover, ширина >= 992px
              const DESKTOP = "(hover: hover) and (pointer: fine) and (min-width: 992px)";
              const scopeToggles = ".list-nav-menu-2 .dropdown-wrapper.w-dropdown > .w-dropdown-toggle";

              function isDesktop() {
                return window.matchMedia(DESKTOP).matches;
              }

              function disableClickOnToggle(toggle) {
                / ЧТО гасим: любые попытки клика/клавиш на самом тоггле
                const cancel = (e) => {
                  if (!isDesktop()) return; / мобилку не трогаем
                  e.preventDefault();
                  e.stopImmediatePropagation();
                };

                / мышь/pointer
                toggle.addEventListener("pointerdown", cancel, true);
                toggle.addEventListener("mousedown", cancel, true);
                toggle.addEventListener("mouseup", cancel, true);
                toggle.addEventListener("click", cancel, true);

                / клавиатура (чтобы Enter/Space не триггерили клик)
                toggle.addEventListener(
                  "keydown",
                  function (e) {
                    if (!isDesktop()) return;
                    if (e.key === "Enter" || e.key === " ") cancel(e);
                  },
                  true,
                );

                / чисто косметика/а11y-подсказка
                toggle.setAttribute("aria-disabled", "true");
              }

              function bind() {
                if (!isDesktop()) return;
                document.querySelectorAll(scopeToggles).forEach(disableClickOnToggle);
              }

              document.addEventListener("DOMContentLoaded", bind);
              / На случай ресайза между брейкпоинтами
              window.addEventListener("resize", function () {
                / Сначала снимаем старые (перезагрузка страницы — самый простой путь),
                / но для стабильности просто перебиндим: addEventListener с capture не дублируется критично.
                bind();
              });
            })();
          </script>

          <script>
            /**
             * Mobile Webflow navbar — Firefox-safe:
             * - при открытии: фиксируем .navbar-3, считаем её высоту
             * - overlay и меню начинаются сразу под шапкой
             * - диммер #menuDimmer под меню
             * - скролл лочится через overflow, без position:fixed на body
             */
            (function () {
              const NAVBAR = ".navbar-3";
              const BTN = `${NAVBAR} .w-nav-button`;
              const OVSEL = ".w-nav-overlay";
              const MENU = ".w-nav-overlay .w-nav-menu";
              const MOBILE = "(max-width: 991px)";
              const isMobile = () => window.matchMedia(MOBILE).matches;

              / диммер
              let dimmer = document.getElementById("menuDimmer");
              if (!dimmer) {
                dimmer = document.createElement("div");
                dimmer.id = "menuDimmer";
                document.body.appendChild(dimmer);
              }

              const $ = (s) => document.querySelector(s);
              const btn = () => $(BTN);
              const ov = () => $(OVSEL);
              const menu = () => $(MENU);
              const open = () => !!$(`${BTN}.w--open`);

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

                / гарантируем, что overlay в body (Firefox)
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
                  });
                }

                const m = menu();
                if (m) {
                  Object.assign(m.style, {
                    position: "fixed",
                    top: h + "px",
                    left: "0",
                    right: "0",
                    bottom: "0", / Растягиваем до низа
                    overflowY: "auto", / Скролл только внутри меню
                    maxHeight: "none", / Снимаем ограничения Webflow
                    WebkitOverflowScrolling: "touch", / Плавность на iOS
                  });
                }

                dimmer.style.top = h + "px";
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
                  / ← убрали проверку !open()
                  o.style.pointerEvents = "none";
                  o.style.display = "none";
                  o.style.height = ""; / сброс возможных залипаний FF
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

              / клик по бургеру
              document.addEventListener("click", (e) => {
                if (e.target.closest(BTN)) {
                  setTimeout(sync, 0);
                }
              });

              / клик по диммеру — закрываем меню
              dimmer.addEventListener("click", () => {
                const b = btn();
                if (b && b.classList.contains("w--open")) b.click();
              });

              / наблюдаем изменения классов/стилей от Webflow
              const mo = new MutationObserver(() => setTimeout(sync, 0));
              mo.observe(document.documentElement, {
                subtree: true,
                childList: true,
                attributes: true,
                attributeFilter: ["class", "style", "data-nav-menu-open"],
              });

              / при ресайзе/скролле актуализируем позицию
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
        </div>
      </div>
      <div class="div-block-59">
        @if(!empty($windowHeroImage))
          {{-- Windows detail page: static product image as background --}}
          <div style="background-image:url('{{ $windowHeroImage }}')" class="div-block-61"></div>
        @else
          {{-- Homepage: video background --}}
          <div class="code-embed-5 w-embed w-script">
            <div id="hero-bg-wrapper" class="video-bg-container">
              <video autoplay="" loop="" muted="" playsinline="">
                <source
                  src="https://s3.amazonaws.com/webflow-prod-assets/6841ddf8ace3d9d9facb14fd/687ca10e41cc245f5cdacfd5_0719_2%20copy.mp4"
                  type="video/mp4"
                />
              </video>
            </div>

            <script>
              const bgWrapper = document.getElementById("hero-bg-wrapper");

              / Твои ссылки:
              const videoUrl =
                "https://s3.amazonaws.com/webflow-prod-assets/6841ddf8ace3d9d9facb14fd/687ca10e41cc245f5cdacfd5_0719_2%20copy.mp4";
              const imageUrl =
                "https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb14fd/69ce36fd76a6aaff9c68df7e_01.webp";

              if (window.innerWidth > 767) {
                / Для компьютеров вставляем видео
                bgWrapper.innerHTML = `
        <video autoplay loop muted playsinline>
          <source src="${videoUrl}" type="video/mp4">
        </video>
      `;
              } else {
                / Для мобильных ставим только твою картинку
                bgWrapper.style.backgroundImage = `url('${imageUrl}')`;
              }
            </script>
          </div>
        @endif
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="title-left---content-right paragraph-content alt hero-page">
            <div class="width-100-mobile-landscape">
              <div class="inner-container _640px _100-tablet">
                <div class="inner-container _450px---tablet">
                  <div class="inner-container _400px---mbl">
                    <h1 class="heading-4">Looking to Replace Your Windows in the Bay Area?</h1>
                    <p
                      data-w-id="c3765d23-1eba-01a8-993c-c59200a6f71b"
                      style="
                        transform: translate3d(0px, 6%, 0px) scale3d(1, 1, 1) rotateX(0deg) rotateY(0deg) rotateZ(0deg)
                          skew(0deg, 0deg);
                        opacity: 0;
                        transform-style: preserve-3d;
                      "
                      class="paragraph-29"
                    >
                      Get Deluxe Windows <br />for Less. 40%&nbsp;OFF* Windows.<br />
                    </p>
                  </div>
                </div>
              </div>
            </div>
            <div class="inner-container _660px _100-tablet">
              <div class="form-block-2 w-form">
                <form
                  id="wf-form-Main-Form"
                  name="wf-form-Main-Form"
                  data-name="Main Form"
                  method="get"
                  class="form-3"
                  data-wf-page-id="6841df5688ca2f74fd53ec90"
                  data-wf-element-id="c3765d23-1eba-01a8-993c-c59200a6f722"
                  aria-label="Main Form"
                >
                  <div class="div-block-22">
                    <h2 class="display-4">Get Deluxe Windows for Less. <br />40%&nbsp;OFF* Windows</h2>
                    <label for="email-banner" class="body-14"></label>
                  </div>
                  <div class="div-block-23">
                    <div>
                      <label for="Name-2" class="field-label">Full name*</label>
                      <div class="input-wrapper">
                        <input
                          class="input icon-left w-input"
                          maxlength="256"
                          name="Name"
                          data-name="Name"
                          placeholder="Full name"
                          type="text"
                          id="name"
                          required=""
                        />
                        <div class="input-line-icon-wrapper"><div class="filled-icons-font"></div></div>
                      </div>
                    </div>
                    <div id="w-node-c3765d23-1eba-01a8-993c-c59200a6f734-fd53ec90" class="div-block-29">
                      <label for="Email-2" class="field-label-2">Email*</label>
                      <div class="input-wrapper">
                        <input
                          class="input icon-left w-input"
                          maxlength="256"
                          name="Email"
                          data-name="Email"
                          placeholder="example@email.com"
                          type="email"
                          id="email"
                          required=""
                        />
                        <div class="input-line-icon-wrapper"><div class="filled-icons-font"></div></div>
                      </div>
                    </div>
                    <div id="w-node-c3765d23-1eba-01a8-993c-c59200a6f73c-fd53ec90">
                      <label for="Phone-2" class="field-label-3">Phone*</label>
                      <div class="input-wrapper">
                        <input
                          class="input icon-left w-input"
                          maxlength="256"
                          name="Phone"
                          data-name="Phone"
                          placeholder="(650) 461-4446"
                          type="tel"
                          id="phone"
                          required=""
                        />
                        <div class="input-line-icon-wrapper"><div class="filled-icons-font"></div></div>
                      </div>
                    </div>
                    <div id="w-node-c3765d23-1eba-01a8-993c-c59200a6f744-fd53ec90">
                      <label for="Company" class="field-label-4">City</label>
                      <div class="input-wrapper">
                        <input
                          class="input icon-left w-input"
                          maxlength="256"
                          name="Subject"
                          data-name="Subject"
                          placeholder="San Francisco"
                          type="text"
                          id="subject"
                          required=""
                        />
                        <div class="input-line-icon-wrapper">
                          <img
                            loading="eager"
                            src="https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb14fd/6841ddf8ace3d9d9facb194d_star-icon-property-x-webflow-template.svg"
                            alt="Star Icon - Property X Webflow Template"
                            width="300"
                            height="150"
                          />
                        </div>
                      </div>
                    </div>
                    <div id="w-node-c3765d23-1eba-01a8-993c-c59200a6f74b-fd53ec90" class="text-area-wrapper">
                      <label for="Message-2" class="field-label-5">Description</label>
                      <div class="input-wrapper">
                        <textarea
                          id="message"
                          name="Message"
                          maxlength="5000"
                          data-name="Message"
                          placeholder="Write your message here..."
                          required=""
                          class="text-area icon-left w-input"
                        ></textarea>
                        <div class="text-area-icon-wrapper">
                          <img
                            loading="eager"
                            src="https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb14fd/6841ddf8ace3d9d9facb192f_lisiting-icon-property-x-webflow-template.svg"
                            alt="Listing Icon - Property X Webflow Template"
                            width="300"
                            height="150"
                          />
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="primary-button space-between-v1">
                    <input
                      type="submit"
                      data-wait="Please wait..."
                      class="inside-input-button text-light w-button"
                      value="Request a Free Estimate"
                    />
                  </div>
                  <label for="email-banner" class="body-14"
                    ><em class="italic-text">*Windows Replacement. Offer Expires </em
                    ><span data-last-day="us-short" class="date-span italic-span"
                      ><em class="italic-text">03/10/26</em></span
                    ></label
                  >
                </form>
                <div class="w-form-done" tabindex="-1" role="region" aria-label="Main Form success">
                  <div>Thank you! Your submission has been received!</div>
                </div>
                <div class="w-form-fail" tabindex="-1" role="region" aria-label="Main Form failure">
                  <div>Oops! Something went wrong while submitting the form.</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
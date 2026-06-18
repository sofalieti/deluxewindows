      @once
        <style>
          .trust-badges-bar {
            --trust-badges-bg-image: none;
          }

          @media (max-width: 991px) {
            .trust-badges-bar.trust-badges--hero-linked {
              background-image: linear-gradient(rgba(11, 37, 66, 0.58), rgba(11, 37, 66, 0.58)), var(--trust-badges-bg-image) !important;
              background-size: cover !important;
              background-position: center center !important;
              background-repeat: no-repeat !important;
            }

            .trust-badges-bar.trust-badges--hero-linked .container-default {
              position: relative;
              z-index: 1;
            }
          }
        </style>
      @endonce

      <div
        data-animation="default"
        data-collapse="tiny"
        data-duration="400"
        data-easing="ease"
        data-easing2="ease"
        role="banner"
        class="navbar w-nav trust-badges-bar"
        id="trustBadgesBar"
      >
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="w-layout-grid grid grid-543">
            <div>
              <div class="w-embed w-script">
                <!-- Elfsight All-in-One Reviews | Untitled All-in-One Reviews 3 -->
                <script src="https://elfsightcdn.com/platform.js" async=""></script>
                <div class="elfsight-app-e3dc666e-7803-4c6a-94c1-0e4f1155d816" data-elfsight-app-lazy=""></div>
              </div>
            </div>
            <div class="div-block-65">
              <div class="text-block-46">✔</div>
              <div class="text-block-47">AAMA Certified Installers<br /></div>
            </div>
            <div class="div-block-65">
              <div class="text-block-46">✔</div>
              <div class="text-block-47">Financing Available<br /></div>
            </div>
            <div class="div-block-65">
              <div class="text-block-46">✔</div>
              <div class="text-block-47">40% Off — Limited Time</div>
            </div>
          </div>
        </div>
        <div class="w-nav-overlay" data-wf-ignore="" id="w-nav-overlay-3"></div>
      </div>

      @once
        <script>
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

            if (document.readyState === "loading") {
              document.addEventListener("DOMContentLoaded", linkTrustBadgesToHeroBg, { once: true });
            } else {
              linkTrustBadgesToHeroBg();
            }
          })();
        </script>
      @endonce
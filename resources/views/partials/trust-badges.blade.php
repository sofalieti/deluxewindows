      <div class="trust-badges-bar" id="trustBadgesBar">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="w-layout-grid grid grid-543">
            <div @if(!empty($trustReviewBadgeBingHide)) class="bing-hide" @endif>
              <div class="w-embed w-script">
                <!-- Elfsight All-in-One Reviews | Untitled All-in-One Reviews 3 -->
                <script src="https://elfsightcdn.com/platform.js" async></script>
                <div class="elfsight-app-e3dc666e-7803-4c6a-94c1-0e4f1155d816" data-elfsight-app-lazy data-ms-review-source></div>
              </div>
              @if(!empty($trustReviewBadgeBingHide))
                <script>
                  if (document.documentElement.classList.contains('dw-bing')) {
                    var reviewBadge = document.querySelector('[data-ms-review-source]');
                    var reviewSlot = document.querySelector('[data-ms-review-slot]');
                    if (reviewBadge && reviewSlot) reviewSlot.appendChild(reviewBadge);
                  }
                </script>
              @endif
            </div>
            <div class="div-block-65">
              <div class="text-block-46" aria-hidden="true">&#10003;</div>
              <div class="text-block-47">AAMA Certified Installers<br /></div>
            </div>
            <div class="div-block-65">
              <div class="text-block-46" aria-hidden="true">&#10003;</div>
              <div class="text-block-47">Financing Available<br /></div>
            </div>
            <div class="div-block-65">
              <div class="text-block-46" aria-hidden="true">&#10003;</div>
              <div class="text-block-47">
                <span class="bing-hide">40% Off — Limited Time</span>
                <span class="bing-only">40% Off on 3+ {{ str_replace(' Or ', ' or ', ucwords(bing_offer_units())) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

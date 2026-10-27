      <div class="trust-badges-bar" id="trustBadgesBar">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="w-layout-grid grid grid-543">
            <div class="ms-review-cell @if(!empty($trustReviewBadgeBingHide)) bing-hide @endif">
              <div class="w-embed w-script">
                <!-- Elfsight All-in-One Reviews | Untitled All-in-One Reviews 3 -->
                <script src="https://elfsightcdn.com/platform.js" async></script>
                <div class="elfsight-app-e3dc666e-7803-4c6a-94c1-0e4f1155d816" data-elfsight-app-lazy data-ms-review-source></div>
              </div>
              <script>
                if (document.documentElement.classList.contains('dw-bing')) {
                  var reviewBadge = document.querySelector('[data-ms-review-source]');
                  var reviewSlot = document.querySelector('[data-ms-review-slot]');
                  if (reviewBadge && reviewSlot) {
                    var reviewCell = reviewBadge.closest('.ms-review-cell');
                    reviewSlot.appendChild(reviewBadge);
                    if (reviewCell) reviewCell.classList.add('bing-hide');
                  }
                }
              </script>
            </div>
            <div class="div-block-65">
              <div class="text-block-46" aria-hidden="true">&#10003;</div>
              <span class="trust-badge-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="9" r="6"/><path d="M8.5 14.5 7 21l5-2.5L17 21l-1.5-6.5"/></svg></span>
              <div class="text-block-47">AAMA Certified Installers</div>
            </div>
            <div class="div-block-65">
              <div class="text-block-46" aria-hidden="true">&#10003;</div>
              <span class="trust-badge-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg></span>
              <div class="text-block-47">Financing Available</div>
            </div>
            <div class="div-block-65">
              <div class="text-block-46" aria-hidden="true">&#10003;</div>
              <span class="trust-badge-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 5 5 19"/><circle cx="7.5" cy="7.5" r="2.5"/><circle cx="16.5" cy="16.5" r="2.5"/></svg></span>
              <div class="text-block-47">
                <span class="bing-hide">40% Off — Limited Time</span>
                <span class="bing-only">40% Off on 3+ {{ str_replace(' Or ', ' or ', ucwords(bing_offer_units())) }}</span>
              </div>
            </div>
            <div class="div-block-65 bing-only">
              <div class="text-block-46" aria-hidden="true">&#10003;</div>
              <span class="trust-badge-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 5 6v6c0 4.5 3 7.5 7 9 4-1.5 7-4.5 7-9V6l-7-3z"/><path d="m9 12 2 2 4-4"/></svg></span>
              <div class="text-block-47">CA Lic. #{{ \App\Services\Seo\OrganizationSchema::CSLB_LICENSE }}</div>
            </div>
          </div>
        </div>
      </div>

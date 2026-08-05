      <section class="section-125">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="w-layout-grid grid-547">
            <div class="div-block-69">
              <div class="bottomfont w-embed">
                <h3 class="faqs-title county-bottom-cta__title">Ready to Replace Your Windows in {{ $ctaLocationLabel ?? $countyName ?? '' }}?</h3>
                Get a free, no-obligation in-home estimate from {{ $ctaLocationLabel ?? $countyName ?? '' }}'s most trusted window experts. We respond within 24 hours and can often schedule same-week visits.
              </div>
            </div>
            <div class="div-block-68">
              <a href="/contacts" class="primary-button-2 add w-inline-block"><div class="additional">Request a Free Estimate</div></a>
              <div class="code-embed-12 w-embed">
                Or call us directly
                <h3 class="faqs-title county-bottom-cta__title">
                  <a href="tel:{{ site_phone_tel() }}" class="county-bottom-cta__phone">✆ {{ site_phone_display() }}</a>
                  <span class="county-bottom-cta__area">Bay Area</span>
                </h3>
                <h3
                  class="faqs-title county-bottom-cta__title county-bottom-cta__local"
                  data-area-phones-local-block
                  @unless(! empty($localPhone)) hidden @endunless
                >
                  <a
                    href="{{ ! empty($localPhone) ? 'tel:'.$localPhone['phone_tel'] : '#' }}"
                    class="county-bottom-cta__phone"
                    data-area-phones-local
                  >✆ <span data-area-phones-local-number>{{ $localPhone['phone_display'] ?? '' }}</span></a>
                  <span class="county-bottom-cta__area" data-area-phones-local-label>{{ $cityName ?? '' }}</span>
                </h3>
              </div>
            </div>
          </div>
        </div>
      </section>

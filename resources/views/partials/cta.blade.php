@php
  $ctaHref = $ctaHref ?? '/contacts';
@endphp
      <section class="section-card-wrapper">
        <div class="section-card cta-v3">
          <div class="w-layout-blockcontainer container-default w-container">
            <div class="w-layout-grid grid-2-columns cta-v3-grid">
              <div id="w-node-f1f26c98-5ff8-6125-e140-c5aec51865d3-fd53ec90" class="z-index-1">
                <div class="inner-container _500px---mbl">
                  <div class="inner-container _480px">
                    <div class="inner-container _450px">
                      <div class="inner-container _300px---mbp">
                        <div>
                          <h2 class="heading-24">Your Dream Home <br />Starts Here</h2>
                        </div>
                      </div>
                    </div>
                    <div class="mg-top-small">
                      <div class="text-neutral-light">
                        <p class="paragraph-20">Tell us about your project — we’ll take care of the rest.</p>
                      </div>
                    </div>
                    <div class="mg-top-default">
                      <div class="buttons-row left">
                        <a
                          id="w-node-_6024598d-eaa2-3e85-ac05-fde8b7e66609-b7e66609"
                          href="{{ $ctaHref }}"
                          class="primary-button w-inline-block"
                          ><div class="text-block">Free Consultation</div></a
                        >
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="image-wrapper cta-v3-image">
                <x-img
                  src="/webflow-assets/images/687ca4b70b8583ef4890bad4_iPad.avif"
                  preset="cta"
                  loading="eager"
                  alt="Deluxe-windows"
                  class="image"
                />
              </div>
            </div>
          </div>
        </div>
      </section>

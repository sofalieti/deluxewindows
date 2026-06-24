      <div
        @if($heroImage)
        style="background-image:url('{{ thumbnail_url($heroImage, 'hero_bg') }}')"
        @endif
        class="div-block-59"
      >
        <div class="div-block-61"></div>
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="title-left---content-right paragraph-content alt hero-page">
            <div class="width-100-mobile-landscape">
              <div class="inner-container _640px _100-tablet">
                <div class="inner-container _450px---tablet">
                  <div class="inner-container _400px---mbl">
                    <div class="div-block-60">
                      <div class="code-embed-7 w-embed">⚲ Serving {{ $cityLabel }}</div>
                      <div class="code-embed-6 w-embed">
                        <h1 class="heading-4">
                          Window Replacement<br>in <span class="city-highlight">{{ $cityLabel }}</span>
                        </h1>
                      </div>
                      <p class="paragraph-62">Professional window &amp; door installation by Bay Area's most trusted team. Vinyl, fiberglass, wood &amp; aluminum — every brand, every style, free estimate.</p>
                      <div class="w-layout-grid grid">
                        <div><div class="text-block-46">✔</div><div class="text-block-47">30+ Years Experience</div></div>
                        <div><div class="text-block-46">✔</div><div class="text-block-47">Employee Owned<br /></div></div>
                        <div><div class="text-block-46">✔</div><div class="text-block-47">Title 24 Compliant</div></div>
                        <div><div class="text-block-46">✔</div><div class="text-block-47">Licensed &amp; Insure</div></div>
                      </div>
                    </div>
                    <div class="hero-mobile-promo-slot hero-mobile-promo-slot--mobile">
                      @include('partials.hero-mobile-promo', [
                        'variant' => 'price',
                        'badgeHtml' => promotion_hero_mobile_price_tag_html(),
                        'buttonLabel' => 'Request a Free Estimate',
                      ])
                    </div>
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
                  data-wf-page-id="69ce7898d019bc268b4bb9e4"
                  data-wf-element-id="b3fcb7c0-398e-469d-7e45-aad63030bc10"
                  aria-label="Main Form"
                >
                  <div class="div-block-22">
                    <div data-estimate-form-promo class="estimate-form-promo promo-offer-context--form">
                      <h2 class="display-4">Get Deluxe Windows for Less. <br />{{ promotion_percent_label() }}* Windows</h2>
                    </div>
                    <label for="email-banner" class="body-14"></label>
                  </div>
                  <div class="div-block-23">
                    <div>
                      <label for="Name-2" class="field-label">Full name*</label>
                      <div class="input-wrapper">
                        <input class="input icon-left w-input" maxlength="256" name="Name" data-name="Name" placeholder="Full name" type="text" id="name" required="" />
                        <div class="input-line-icon-wrapper"><div class="filled-icons-font">&#xE896;</div></div>
                      </div>
                    </div>
                    <div id="w-node-b3fcb7c0-398e-469d-7e45-aad63030bc22-8b4bb9e4" class="div-block-29">
                      <label for="Email-2" class="field-label-2">Email*</label>
                      <div class="input-wrapper">
                        <input class="input icon-left w-input" maxlength="256" name="Email" data-name="Email" placeholder="example@email.com" type="email" id="email" required="" />
                        <div class="input-line-icon-wrapper"><div class="filled-icons-font">&#xE88F;</div></div>
                      </div>
                    </div>
                    <div id="w-node-b3fcb7c0-398e-469d-7e45-aad63030bc2a-8b4bb9e4">
                      <label for="Phone-2" class="field-label-3">Phone*</label>
                      <div class="input-wrapper">
                        <input class="input icon-left w-input" maxlength="256" name="Phone" data-name="Phone" placeholder="{{ site_phone_display() }}" type="tel" id="phone" required="" />
                        <div class="input-line-icon-wrapper"><div class="filled-icons-font">&#xE873;</div></div>
                      </div>
                    </div>
                    <div id="w-node-b3fcb7c0-398e-469d-7e45-aad63030bc32-8b4bb9e4">
                      <label for="Company" class="field-label-4">City</label>
                      <div class="input-wrapper">
                        <input class="input icon-left w-input" maxlength="256" name="Subject" data-name="Subject" placeholder="{{ $cityName }}" type="text" id="subject" required="" />
                        <div class="input-line-icon-wrapper">
                          <img loading="eager" src="/webflow-assets/images/6841ddf8ace3d9d9facb194d_star-icon-property-x-webflow-template.svg" alt="Star Icon - Property X Webflow Template" />
                        </div>
                      </div>
                    </div>
                    <div id="w-node-b3fcb7c0-398e-469d-7e45-aad63030bc39-8b4bb9e4" class="text-area-wrapper">
                      <label for="Message-2" class="field-label-5">Description</label>
                      <div class="input-wrapper">
                        <textarea id="message" name="Message" maxlength="5000" data-name="Message" placeholder="Write your message here..." required="" class="text-area icon-left w-input"></textarea>
                        <div class="text-area-icon-wrapper">
                          <img loading="eager" src="/webflow-assets/images/6841ddf8ace3d9d9facb192f_lisiting-icon-property-x-webflow-template.svg" alt="Listing Icon - Property X Webflow Template" />
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="primary-button space-between-v1">
                    <input type="submit" data-wait="Please wait..." class="inside-input-button text-light w-button" value="Request a Free Estimate" />
                  </div>
                  <label for="email-banner" class="body-14">
                    <em class="italic-text">*Windows Replacement. Offer Expires </em>
                    <span class="date-span italic-span"><em class="italic-text">{{ promotion_date('us-short') }}</em></span>
                  </label>
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

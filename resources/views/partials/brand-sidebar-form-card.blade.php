        <div class="card-2 sidebar-v1---card new-design {{ $variant ?? 'hero-section' }}">
          @if(($variant ?? 'hero-section') !== 'brands')
          <div class="inner-container _400px---mbl">
            <div class="text-titles-3">
              <div class="display-41 mid">Get Deluxe Windows for Less. {{ promotion_percent_label() }}* Windows</div>
            </div>
            <div class="mg-top-small-4">
              <p class="text-titles-3"><em>Request a FREE No-Obligation Quote &amp; Expert Advice!</em><br></p>
            </div>
          </div>
          <div class="mg-top-default-4">
          @else
          <div class="form-sidebar">
          @endif
            <div class="{{ ($variant ?? 'hero-section') === 'brands' ? 'form-block-3' : 'sidebar-form-block-v1 sidebar' }} w-form">
              <form
                id="wf-form-Property-Form"
                name="wf-form-Property-Form"
                data-name="Property Form"
                method="get"
                class="form-wrapper"
                data-wf-page-id="{{ $wfPageId ?? '6841ddf8ace3d9d9facb1583' }}"
                aria-label="Property Form"
              >
                <div class="grid-1-column-2 gap-row-12">
                  <div class="input-wrapper-5">
                    <div class="input-line-icon-wrapper-4"><div class="filled-icons-font"></div></div>
                    <input class="input-2 icon-left w-input" maxlength="256" name="Name" data-name="Name" placeholder="Full name" type="text" id="name" required="" />
                  </div>
                  <div class="input-wrapper-5">
                    <div class="input-line-icon-wrapper-4"><div class="filled-icons-font"></div></div>
                    <input class="input-2 icon-left w-input" maxlength="256" name="Email" data-name="Email" placeholder="Email address" type="email" id="email" required="" />
                  </div>
                  <div class="input-wrapper-5">
                    <div class="input-line-icon-wrapper-4"><div class="filled-icons-font"></div></div>
                    <input class="input-2 icon-left w-input" maxlength="256" name="Phone" data-name="Phone" placeholder="Phone number" type="tel" id="phone" required="" />
                  </div>
                  <div class="input-wrapper-5">
                    <input class="input-2 icon-left w-input" maxlength="256" name="Subject" data-name="Subject" placeholder="City" type="text" id="subject" required="" />
                    <div class="input-line-icon-wrapper">
                      <img loading="eager" src="/webflow-assets/images/6841ddf8ace3d9d9facb194d_star-icon-property-x-webflow-template.svg" alt="Star Icon - Property X Webflow Template" />
                    </div>
                  </div>
                  <div class="primary-button-6 space-between-v1">
                    <input type="submit" data-wait="Please wait..." class="inside-input-button-4 text-light w-button" value="Get Your Free Estimate" />
                  </div>
                </div>
              </form>
              <div class="success-message-wrapper w-form-done" tabindex="-1" role="region" aria-label="Property Form success">
                <div class="item-icon-left"><div class="icon-font-rounded-5 success-message-icon"></div></div>
                <div class="mg-top-extra-small-2">
                  <div class="text-titles-3"><div class="display-40">Thank you! We’ll get back to you soon<br></div></div>
                </div>
              </div>
              <div class="error-message-wrapper-4 w-form-fail" tabindex="-1" role="region" aria-label="Property Form failure">
                <div>Oops! Something went wrong.</div>
              </div>
            </div>
          @if(($variant ?? 'hero-section') !== 'brands')
          </div>
          @else
          </div>
          @endif
        </div>

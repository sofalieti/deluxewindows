      <section id="contact" class="section hero-v4">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="w-layout-grid grid-2-columns contact-grid-v2">
            <div class="inner-container _440px _100-tablet">
              <div class="inner-container _550px---tablet">
                <h1>Schedule Your Coupon</h1>
                <div class="mg-top-small">
                  <p class="paragraph-8">We’re here to help with all your door and window needs.</p>
                </div>
              </div>
              <div class="mg-top-default">
                <div class="w-layout-grid grid-2-columns contact-links-grid-v1">
                  <div class="contact-link---icon-left">
                    <img
                      src="/webflow-assets/images/6841ddf8ace3d9d9facb1950_phone-icon-property-x-webflow-template.svg"
                      loading="eager"
                      alt="Phone Icon - Property X Webflow Template"
                      class="contact-icon"
                    />
                    <div>
                      <div class="div-block"><div class="text-block-3">Phone number</div></div>
                      <div class="mg-top-tiny">
                        <a href="tel:{{ site_phone_tel() }}" class="link mid w-inline-block"><div>{{ site_phone_display() }}</div></a>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <div class="inner-container _659px width-100 _100-tablet">
              <div class="form-block-2 w-form">
                <form
                  id="email-form-2"
                  name="email-form-2"
                  data-name="Email Form 2"
                  method="get"
                  class="form-4"
                  data-wf-page-id="687a4292617b9b4ed5cfe680"
                  data-wf-element-id="5b9eb27e-57ba-7166-a0b8-a976f39b6cb8"
                  aria-label="Email Form 2"
                >
                  <div class="div-block-24">
                    <h2 class="display-33">Get Deluxe Windows for Less. 40% OFF* Windows</h2>
                    <label for="email-banner" class="body-14">
                      <em class="italic-text">*Windows Replacement. Offer Expires </em>
                      <span class="date-span italic-span"><em class="italic-text">{{ promotion_date('us-short') }}</em></span>
                    </label>
                    <label for="email-banner" class="body-14">Request a FREE No-Obligation Quote &amp; Expert Advice!</label>
                  </div>
                  <div class="div-block-25">
                    <div>
                      <label for="Name-2">Full name*</label>
                      <div class="input-wrapper-2">
                        <input class="input-2 icon-left w-input" maxlength="256" name="Name" data-name="Name" placeholder="Full name" type="text" id="name" required="" />
                        <div class="input-line-icon-wrapper-2"><div class="filled-icons-font">&#xE896;</div></div>
                      </div>
                    </div>
                    <div class="div-block-35">
                      <label for="Email-2">Email*</label>
                      <div class="input-wrapper-2">
                        <input class="input-2 icon-left w-input" maxlength="256" name="Email" data-name="Email" placeholder="example@email.com" type="email" id="email" required="" />
                        <div class="input-line-icon-wrapper-2"><div class="filled-icons-font">&#xE88F;</div></div>
                      </div>
                    </div>
                    <div>
                      <label for="Phone-2">Phone*</label>
                      <div class="input-wrapper-2">
                        <input class="input-2 icon-left w-input" maxlength="256" name="Phone" data-name="Phone" placeholder="{{ site_phone_display() }}" type="tel" id="phone" required="" />
                        <div class="input-line-icon-wrapper-2"><div class="filled-icons-font">&#xE873;</div></div>
                      </div>
                    </div>
                    <div>
                      <label for="Company">City</label>
                      <div class="input-wrapper-2">
                        <input class="input-2 icon-left w-input" maxlength="256" name="Subject" data-name="Subject" placeholder="San Francisco" type="text" id="subject" required="" />
                        <div class="input-line-icon-wrapper-2">
                          <img loading="eager" src="/webflow-assets/images/6841ddf8ace3d9d9facb194d_star-icon-property-x-webflow-template.svg" alt="Star Icon - Property X Webflow Template" />
                        </div>
                      </div>
                    </div>
                    <div class="text-area-wrapper">
                      <label for="Message-2">Listing short description</label>
                      <div class="input-wrapper-2">
                        <textarea id="message" name="Message" maxlength="5000" data-name="Message" placeholder="Write your message here..." required="" class="text-area-2 icon-left w-input"></textarea>
                        <div class="text-area-icon-wrapper-2">
                          <img loading="eager" src="/webflow-assets/images/6841ddf8ace3d9d9facb192f_lisiting-icon-property-x-webflow-template.svg" alt="Listing Icon - Property X Webflow Template" />
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="primary-button-2 space-between-v1">
                    <input type="submit" data-wait="Please wait..." class="inside-input-button-2 text-light w-button" value="Get your free in-home estimate" />
                  </div>
                </form>
                <div class="w-form-done" tabindex="-1" role="region" aria-label="Email Form 2 success">
                  <div>Thank you! Your submission has been received!</div>
                </div>
                <div class="w-form-fail" tabindex="-1" role="region" aria-label="Email Form 2 failure">
                  <div>Oops! Something went wrong while submitting the form.</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

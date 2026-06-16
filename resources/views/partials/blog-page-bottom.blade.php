      <section class="section-card-wrapper"></section>

      <section class="section-card-wrapper">
        <div class="section-card cta-v3">
          <div class="w-layout-blockcontainer container-default w-container">
            <div class="w-layout-grid grid-2-columns cta-v3-grid">
              <div id="w-node-_18d2b5aa-d38c-0c17-0c65-2877a44c97c5-ec241dd4" class="z-index-1">
                <div class="inner-container _500px---mbl">
                  <div class="inner-container _480px">
                    <div class="inner-container _450px">
                      <div class="inner-container _300px---mbp">
                        <div class="mg-top-small">
                          <h2 class="{{ $ctaHeadingClass ?? 'heading-28' }}">Your Dream Home Starts Here.</h2>
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
                          href="#contact"
                          class="primary-button w-inline-block"
                        ><div class="text-block">Free Consultation</div></a>
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

      <section id="contact" class="section hero-v4">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="w-layout-grid grid-2-columns contact-grid-v2">
            <div id="w-node-_824fa87e-09e3-541d-f82c-49c3ae0a3f2b-ec241dd4" class="inner-container _440px _100-tablet">
              <div class="inner-container _550px---tablet">
                <h1 class="{{ $contactHeadingClass ?? '' }}">Contact Us</h1>
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
            <div id="w-node-_824fa87e-09e3-541d-f82c-49c3ae0a3f3e-ec241dd4" class="inner-container _659px width-100 _100-tablet">
              <div class="form-block-2 w-form">
                <form
                  id="email-form-2"
                  name="email-form-2"
                  data-name="Email Form 2"
                  method="get"
                  class="form-3"
                  data-wf-page-id="{{ $wfPageId ?? '688097fa174129b5ec241dd4' }}"
                  aria-label="Email Form 2"
                >
                  <div class="div-block-22">
                    <h2 class="display-4">Get Deluxe Windows for Less. 40%&nbsp;OFF* Windows</h2>
                    <label for="email-banner" class="body-14"><em class="italic-text">*Windows Replacement. Offer Expires </em><span class="date-span italic-span">{{ promotion_date('us-short') }}</span></label>
                    <label for="email-banner" class="body-14">Request a FREE No-Obligation Quote &amp; Expert Advice!</label>
                  </div>
                  <div class="div-block-23">
                    <div>
                      <label for="Name-2">Full name*</label>
                      <div class="input-wrapper">
                        <input class="input icon-left w-input" maxlength="256" name="Name" data-name="Name" placeholder="Full name" type="text" id="name" required="" />
                        <div class="input-line-icon-wrapper"><div class="filled-icons-font" aria-hidden="true">&#xE896;</div></div>
                      </div>
                    </div>
                    <div id="w-node-_824fa87e-09e3-541d-f82c-49c3ae0a3f52-ec241dd4" class="div-block-36">
                      <label for="Email-2">Email address*</label>
                      <div class="input-wrapper">
                        <input class="input icon-left w-input" maxlength="256" name="Email" data-name="Email" placeholder="example@email.com" type="email" id="email" required="" />
                        <div class="input-line-icon-wrapper"><div class="filled-icons-font" aria-hidden="true">&#xE88F;</div></div>
                      </div>
                    </div>
                    <div id="w-node-_824fa87e-09e3-541d-f82c-49c3ae0a3f5a-ec241dd4">
                      <label for="Phone-2">Phone number*</label>
                      <div class="input-wrapper">
                        <input class="input icon-left w-input" maxlength="256" name="Phone" data-name="Phone" placeholder="{{ site_phone_display() }}" type="tel" id="phone" required="" />
                        <div class="input-line-icon-wrapper"><div class="filled-icons-font" aria-hidden="true">&#xE873;</div></div>
                      </div>
                    </div>
                    <div id="w-node-_824fa87e-09e3-541d-f82c-49c3ae0a3f62-ec241dd4">
                      <label for="Company">City</label>
                      <div class="input-wrapper">
                        <input class="input icon-left w-input" maxlength="256" name="Subject" data-name="Subject" placeholder="San Francisco" type="text" id="subject" required="" />
                        <div class="input-line-icon-wrapper">
                          <img loading="eager" src="/webflow-assets/images/6841ddf8ace3d9d9facb194d_star-icon-property-x-webflow-template.svg" alt="Star Icon - Property X Webflow Template" />
                        </div>
                      </div>
                    </div>
                    <div id="w-node-_824fa87e-09e3-541d-f82c-49c3ae0a3f69-ec241dd4" class="text-area-wrapper">
                      <label for="Message-2">Listing short description</label>
                      <div class="input-wrapper">
                        <textarea id="message" name="Message" maxlength="5000" data-name="Message" placeholder="Write your message here..." required="" class="text-area icon-left w-input"></textarea>
                        <div class="text-area-icon-wrapper">
                          <img loading="eager" src="/webflow-assets/images/6841ddf8ace3d9d9facb192f_lisiting-icon-property-x-webflow-template.svg" alt="Listing Icon - Property X Webflow Template" />
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="primary-button space-between-v1">
                    <input type="submit" data-wait="Please wait..." class="inside-input-button text-light w-button" value="Get your free  in-home estimate" />
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

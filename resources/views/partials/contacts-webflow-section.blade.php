      <section id="contact" class="section hero-v4">
        <div class="w-layout-blockcontainer container-default w-container">
          <h1 class="heading-31">Contact Us</h1>
          <div class="w-layout-grid grid-2-columns contact-grid-v2">
            <div class="inner-container _440px _100-tablet">
              <div class="inner-container _550px---tablet">
                <div class="mg-top-small">
                  <p class="paragraph-8">We’re here to help with all your door and window needs. We cover the entire Bay Area.</p>
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
              <div class="card form-wrapper">
                <div id="w-node-_72a3ee2c-7bdf-4667-09bd-bdd093d9fa35-93d9fa34" class="form-wrapper-v1 w-form">
                  <form
                    id="wf-form-Contact-V1-Form"
                    name="wf-form-Contact-V1-Form"
                    data-name="Contact V1 Form"
                    method="get"
                    class="form"
                    data-wf-page-id="6841ddf8ace3d9d9facb1638"
                    data-wf-element-id="72a3ee2c-7bdf-4667-09bd-bdd093d9fa36"
                    aria-label="Contact V1 Form"
                  >
                    <div class="w-layout-grid grid-form">
                      <div id="w-node-_72a3ee2c-7bdf-4667-09bd-bdd093d9fa38-93d9fa34">
                        <label for="Name-2">Full name*</label>
                        <div class="input-wrapper">
                          <input class="input icon-left w-input" maxlength="256" name="Name" data-name="Name" placeholder="Full name" type="text" id="name" required="" />
                          <div class="input-line-icon-wrapper"><div class="filled-icons-font">&#xE896;</div></div>
                        </div>
                      </div>
                      <div id="w-node-_72a3ee2c-7bdf-4667-09bd-bdd093d9fa40-93d9fa34">
                        <label for="Email-2">Email address*</label>
                        <div class="input-wrapper">
                          <input class="input icon-left w-input" maxlength="256" name="Email" data-name="Email" placeholder="example@email.com" type="email" id="email" required="" />
                          <div class="input-line-icon-wrapper"><div class="filled-icons-font">&#xE88F;</div></div>
                        </div>
                      </div>
                      <div id="w-node-_72a3ee2c-7bdf-4667-09bd-bdd093d9fa48-93d9fa34">
                        <label for="Phone-2">Phone number*</label>
                        <div class="input-wrapper">
                          <input class="input icon-left w-input" maxlength="256" name="Phone" data-name="Phone" placeholder="{{ site_phone_display() }}" type="tel" id="phone" required="" />
                          <div class="input-line-icon-wrapper"><div class="filled-icons-font">&#xE873;</div></div>
                        </div>
                      </div>
                      <div id="w-node-_72a3ee2c-7bdf-4667-09bd-bdd093d9fa50-93d9fa34">
                        <label for="Company">City</label>
                        <div class="input-wrapper">
                          <input class="input icon-left w-input" maxlength="256" name="Subject" data-name="Subject" placeholder="San Francisco" type="text" id="subject" required="" />
                          <div class="input-line-icon-wrapper">
                            <img loading="eager" src="/webflow-assets/images/6841ddf8ace3d9d9facb194d_star-icon-property-x-webflow-template.svg" alt="Star Icon - Property X Webflow Template" />
                          </div>
                        </div>
                      </div>
                      <div id="w-node-_72a3ee2c-7bdf-4667-09bd-bdd093d9fa57-93d9fa34" class="text-area-wrapper">
                        <label for="Message-2">Listing short description</label>
                        <div class="input-wrapper">
                          <textarea id="message" name="Message" maxlength="5000" data-name="Message" placeholder="Write your message here..." required="" class="text-area icon-left w-input"></textarea>
                          <div class="text-area-icon-wrapper">
                            <img loading="eager" src="/webflow-assets/images/6841ddf8ace3d9d9facb192f_lisiting-icon-property-x-webflow-template.svg" alt="Listing Icon - Property X Webflow Template" />
                          </div>
                        </div>
                      </div>
                      <div id="w-node-_72a3ee2c-7bdf-4667-09bd-bdd093d9fa5e-93d9fa34">
                        <div class="primary-button space-between-v1">
                          <input type="submit" data-wait="Please wait..." class="inside-input-button text-light w-button" value="Request a Free Estimate" />
                        </div>
                      </div>
                    </div>
                  </form>
                  <div class="success-message-wrapp w-form-done" tabindex="-1" role="region" aria-label="Contact V1 Form success">
                    <div class="success-icon"><div class="icon-font-rounded">&#xE832;</div></div>
                    <div class="mg-top-default">
                      <div class="text-titles">
                        <div class="display-5 mid">Thank you! We’ll get back to <span class="text-no-wrap">you soon</span></div>
                      </div>
                    </div>
                    <div class="mg-top-extra-small">
                      <p class="paragraph-medium">We have received your message and will get back to you as soon as possible. Our team is dedicated to providing the best support and we appreciate <span class="text-no-wrap">your patience.</span></p>
                    </div>
                  </div>
                  <div class="error-message-wrapper w-form-fail" tabindex="-1" role="region" aria-label="Contact V1 Form failure">
                    <div>Oops! Something went wrong.</div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

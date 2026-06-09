@php
  $brandLikeHero = !empty($brandHero) || !empty($windowTypeHero) || !empty($collectionHero) || !empty($doorHero);
  $heroPageId = !empty($collectionHero)
    ? '69366119c296b5e2e8bdbfb8'
    : (!empty($windowTypeHero)
      ? '688e50676f1dbd8cba0e091a'
      : (!empty($brandHero)
        ? '6841ddf8ace3d9d9facb1583'
        : (!empty($doorHero)
          ? '6841ddf8ace3d9d9facb156f'
          : (!empty($windowHeroImage) ? '6841ddf8ace3d9d9facb1582' : '6841df5688ca2f74fd53ec90'))));
  $heroElementId = !empty($collectionHero)
    ? '89b9e427-c9d3-6d8b-0afb-075923310b6c'
    : (!empty($windowTypeHero)
      ? '3ab01c22-18de-4545-ffef-5a89d31afac2'
      : (!empty($brandHero)
        ? 'dc04ee7a-918f-7eb9-bff0-f9899431c4c3'
        : (!empty($doorHero)
          ? '553713b0-acde-64e1-6dce-d76cee8c0ff6'
          : (!empty($windowHeroImage) ? '0d2c5edc-6a74-d360-f6d2-0a02682efe78' : 'c3765d23-1eba-01a8-993c-c59200a6f722'))));
  $heroEmailNode = !empty($collectionHero)
    ? 'w-node-_89b9e427-c9d3-6d8b-0afb-075923310b7e-e8bdbfb8'
    : (!empty($windowTypeHero)
      ? 'w-node-_3ab01c22-18de-4545-ffef-5a89d31afad4-ba0e091a'
      : (!empty($brandHero)
        ? 'w-node-dc04ee7a-918f-7eb9-bff0-f9899431c4d5-facb1583'
        : (!empty($doorHero)
          ? 'w-node-_553713b0-acde-64e1-6dce-d76cee8c1008-facb156f'
          : (!empty($windowHeroImage) ? 'w-node-_0d2c5edc-6a74-d360-f6d2-0a02682efe8a-facb1582' : 'w-node-c3765d23-1eba-01a8-993c-c59200a6f734-fd53ec90'))));
  $heroPhoneNode = !empty($collectionHero)
    ? 'w-node-_89b9e427-c9d3-6d8b-0afb-075923310b86-e8bdbfb8'
    : (!empty($windowTypeHero)
      ? 'w-node-_3ab01c22-18de-4545-ffef-5a89d31afadc-ba0e091a'
      : (!empty($brandHero)
        ? 'w-node-dc04ee7a-918f-7eb9-bff0-f9899431c4dd-facb1583'
        : (!empty($doorHero)
          ? 'w-node-_553713b0-acde-64e1-6dce-d76cee8c1010-facb156f'
          : (!empty($windowHeroImage) ? 'w-node-_0d2c5edc-6a74-d360-f6d2-0a02682efe92-facb1582' : 'w-node-c3765d23-1eba-01a8-993c-c59200a6f73c-fd53ec90'))));
  $heroCityNode = !empty($collectionHero)
    ? 'w-node-_89b9e427-c9d3-6d8b-0afb-075923310b8e-e8bdbfb8'
    : (!empty($windowTypeHero)
      ? 'w-node-_3ab01c22-18de-4545-ffef-5a89d31afae4-ba0e091a'
      : (!empty($brandHero)
        ? 'w-node-dc04ee7a-918f-7eb9-bff0-f9899431c4e5-facb1583'
        : (!empty($doorHero)
          ? 'w-node-_553713b0-acde-64e1-6dce-d76cee8c1018-facb156f'
          : (!empty($windowHeroImage) ? 'w-node-_0d2c5edc-6a74-d360-f6d2-0a02682efe9a-facb1582' : 'w-node-c3765d23-1eba-01a8-993c-c59200a6f744-fd53ec90'))));
  $heroMessageNode = !empty($collectionHero)
    ? 'w-node-_89b9e427-c9d3-6d8b-0afb-075923310b95-e8bdbfb8'
    : (!empty($windowTypeHero)
      ? 'w-node-_3ab01c22-18de-4545-ffef-5a89d31afaeb-ba0e091a'
      : (!empty($brandHero)
        ? 'w-node-dc04ee7a-918f-7eb9-bff0-f9899431c4ec-facb1583'
        : (!empty($doorHero)
          ? 'w-node-_553713b0-acde-64e1-6dce-d76cee8c101f-facb156f'
          : (!empty($windowHeroImage) ? 'w-node-_0d2c5edc-6a74-d360-f6d2-0a02682efea1-facb1582' : 'w-node-c3765d23-1eba-01a8-993c-c59200a6f74b-fd53ec90'))));
  $heroPricingBlockClass = !empty($collectionHero)
    ? 'rich-text-block-3'
    : (!empty($windowTypeHero) ? 'rich-text-block-7' : 'rich-text-block-5');
  $heroPricingHtml = !empty($collectionHero)
    ? ($heroFormHtml ?? '<h3><strong><code>40% off for limited time</code></strong></h3><p>Starting from <s>915</s> $549<sup>*</sup></p><p><code>per window</code></p>')
    : (!empty($windowTypeHero)
      ? ($heroFormHtml ?? '<p>Starting from $1199 per window installed.</p><p><strong>Special pricing available upon request! </strong>‍</p>')
      : ($brandHeroFormHtml ?? '<p>Starting from $999 per window installed.</p><p><strong>Special pricing available upon request!</strong>‍</p>'));
@endphp

@include('partials.header-scripts')

      <div class="div-block-59">
        @if(!empty($heroBackgroundImage))
          {{-- Static hero background (brand pages etc.) --}}
          <div style="background-image:url('{{ thumbnail_url($heroBackgroundImage, 'hero_bg') }}')" class="div-block-61"></div>
        @elseif(!empty($doorHeroImage))
          {{-- Doors detail page: featured image as background --}}
          <div style="background-image:url('{{ thumbnail_url($doorHeroImage, 'hero_bg') }}')" class="div-block-61"></div>
        @elseif(!empty($windowHeroImage))
          {{-- Windows detail page: static product image as background --}}
          <div style="background-image:url('{{ thumbnail_url($windowHeroImage, 'hero_bg') }}')" class="div-block-61"></div>
        @else
          {{-- Homepage: video background --}}
          <div class="code-embed-5 w-embed w-script">
            <div id="hero-bg-wrapper" class="video-bg-container">
              <video autoplay="" loop="" muted="" playsinline="">
                <source
                  src="/webflow-assets/videos/687ca10e41cc245f5cdacfd5_0719_2-copy.mp4"
                  type="video/mp4"
                />
              </video>
            </div>

            <script>
              const bgWrapper = document.getElementById("hero-bg-wrapper");
              // Local asset paths:
              const videoUrl = "/webflow-assets/videos/687ca10e41cc245f5cdacfd5_0719_2-copy.mp4";
              const imageUrl = @json(thumbnail_url('/webflow-assets/images/69ce36fd76a6aaff9c68df7e_01.webp', 'hero_mobile'));

              if (window.innerWidth > 767) {
                // Desktop: show video
                bgWrapper.innerHTML = `
        <video autoplay loop muted playsinline>
          <source src="${videoUrl}" type="video/mp4">
        </video>
      `;
              } else {
                // Mobile: show static image
                bgWrapper.style.backgroundImage = `url('${imageUrl}')`;
              }
            </script>
          </div>
        @endif
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="title-left---content-right paragraph-content alt hero-page">
            <div class="width-100-mobile-landscape">
              <div class="inner-container _640px _100-tablet">
                <div class="inner-container _450px---tablet">
                  <div class="inner-container _400px---mbl">
                    @if($brandLikeHero || !empty($windowHeroImage) || !empty($doorHero))
                    @if($brandLikeHero || !empty($doorHero))
                    <div class="div-block-60">
                    @endif
                    <div class="rich-text-block-2 w-richtext">
                      <h2 class="heading-49">Upgrade to Energy Efficient Windows and&nbsp;Doors for Less</h2>
                      <p>‍</p>
                      <div class="w-embed">
                        <h2 style="font-size: 21px; color: #fff" data-city="">Local Installers</h2>
                      </div>
                    </div>
                    @if($brandLikeHero || !empty($doorHero))
                    </div>
                    @endif
                    @else
                    <h1 class="heading-4">Looking to Replace Your Windows in the Bay Area?</h1>
                    <p
                      data-w-id="c3765d23-1eba-01a8-993c-c59200a6f71b"
                      style="
                        transform: translate3d(0px, 6%, 0px) scale3d(1, 1, 1) rotateX(0deg) rotateY(0deg) rotateZ(0deg)
                          skew(0deg, 0deg);
                        opacity: 0;
                        transform-style: preserve-3d;
                      "
                      class="paragraph-29"
                    >
                      Get Deluxe Windows <br />for Less. 40%&nbsp;OFF* Windows.<br />
                    </p>
                    @endif
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
                  data-wf-page-id="{{ $heroPageId }}"
                  data-wf-element-id="{{ $heroElementId }}"
                  @if(empty($windowHeroImage) && !$brandLikeHero) aria-label="Main Form" @endif
                >
                  <div class="div-block-22">
                    @if($brandLikeHero)
                    @if(!empty($brandLogo))
                    <img loading="lazy" src="{{ $brandLogo }}" alt="" class="svg50 sidebar-svg top-svg" />
                    @endif
                    <label for="email-banner" class="body-14"></label>
                    <div class="{{ $heroPricingBlockClass }} w-richtext">
                      {!! $heroPricingHtml !!}
                    </div>
                    @elseif(!empty($doorHero) && !empty($doorDiscountHtml))
                    <label for="email-banner" class="body-14"></label>
                    <div class="rich-text-block-6 w-richtext">
                      {!! $doorDiscountHtml !!}
                    </div>
                    @elseif(!empty($windowHeroImage) && !empty($windowDiscountHtml))
                    <label for="email-banner" class="body-14"></label>
                    <div class="rich-text-block-4 w-richtext">
                      {!! $windowDiscountHtml !!}
                    </div>
                    @else
                    <h2 class="display-4">Get Deluxe Windows for Less. <br />40%&nbsp;OFF* Windows</h2>
                    <label for="email-banner" class="body-14"></label>
                    @endif
                  </div>
                  <div class="div-block-23">
                    <div>
                      <label for="Name-2" class="field-label">Full name*</label>
                      <div class="input-wrapper">
                        <input
                          class="input icon-left w-input"
                          maxlength="256"
                          name="Name"
                          data-name="Name"
                          placeholder="Full name"
                          type="text"
                          id="name"
                          required=""
                        />
                        <div class="input-line-icon-wrapper"><div class="filled-icons-font"></div></div>
                      </div>
                    </div>
                    <div id="{{ $heroEmailNode }}" class="div-block-29">
                      <label for="Email-2" class="field-label-2">Email*</label>
                      <div class="input-wrapper">
                        <input
                          class="input icon-left w-input"
                          maxlength="256"
                          name="Email"
                          data-name="Email"
                          placeholder="example@email.com"
                          type="email"
                          id="email"
                          required=""
                        />
                        <div class="input-line-icon-wrapper"><div class="filled-icons-font"></div></div>
                      </div>
                    </div>
                    <div id="{{ $heroPhoneNode }}">
                      <label for="Phone-2" class="field-label-3">Phone*</label>
                      <div class="input-wrapper">
                        <input
                          class="input icon-left w-input"
                          maxlength="256"
                          name="Phone"
                          data-name="Phone"
                          placeholder="(650) 461-4446"
                          type="tel"
                          id="phone"
                          required=""
                        />
                        <div class="input-line-icon-wrapper"><div class="filled-icons-font"></div></div>
                      </div>
                    </div>
                    <div id="{{ $heroCityNode }}">
                      <label for="Company" class="field-label-4">City</label>
                      <div class="input-wrapper">
                        <input
                          class="input icon-left w-input"
                          maxlength="256"
                          name="Subject"
                          data-name="Subject"
                          placeholder="San Francisco"
                          type="text"
                          id="subject"
                          required=""
                        />
                        <div class="input-line-icon-wrapper">
                          <img
                            loading="eager"
                            src="/webflow-assets/images/6841ddf8ace3d9d9facb194d_star-icon-property-x-webflow-template.svg"
                            alt="Star Icon - Property X Webflow Template"
                            @if(empty($windowHeroImage)) style="width:18px;height:18px;object-fit:contain;" @endif
                          />
                        </div>
                      </div>
                    </div>
                    <div id="{{ $heroMessageNode }}" class="text-area-wrapper">
                      <label for="Message-2" class="field-label-5">Description</label>
                      <div class="input-wrapper">
                        <textarea
                          id="message"
                          name="Message"
                          maxlength="5000"
                          data-name="Message"
                          placeholder="Write your message here..."
                          required=""
                          class="text-area icon-left w-input"
                        ></textarea>
                        <div class="text-area-icon-wrapper">
                          <img
                            loading="eager"
                            src="/webflow-assets/images/6841ddf8ace3d9d9facb192f_lisiting-icon-property-x-webflow-template.svg"
                            alt="Listing Icon - Property X Webflow Template"
                            @if(empty($windowHeroImage)) style="width:18px;height:18px;object-fit:contain;" @endif
                          />
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="primary-button space-between-v1">
                    <input
                      type="submit"
                      data-wait="Please wait..."
                      class="inside-input-button text-light w-button"
                      value="Request a Free Estimate"
                    />
                  </div>
                  <label for="email-banner" class="body-14"
                    ><em class="italic-text">*Windows Replacement. Offer Expires </em
                    ><span data-last-day="us-short" class="date-span italic-span"
                      ><em class="italic-text">03/10/26</em></span
                    ></label
                  >
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
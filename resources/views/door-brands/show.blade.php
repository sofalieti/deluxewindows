@extends('layouts.classic')

@section('wfPage', '6841ddf8ace3d9d9facb1583')
@section('bodyClass', 'body-18 height-auto')
@section('htmlClass', '')

@section('content')
      @include('partials.hero', [
        'brandHero' => true,
        'doorBrandHero' => true,
        'brandName' => $name,
        'brandLogo' => $logo,
        'heroBackgroundImage' => $featuredImage,
        'windowHeroImage' => null,
        'brandHeroFormHtml' => $brandHeroFormHtml,
        'brandPromotionPricing' => $brandPromotionPricing ?? null,
        'hideHeroPromoWhenEmpty' => true,
      ])

      @include('partials.trust-badges')

      <section class="section_breadcrumbs section-121">
        <div class="w-layout-blockcontainer container-default breadcrumbs-container w-container">
          <div class="breadcrumbs-wrapper">
            <a href="/" class="breadcrumb-link">Home</a>
            <div class="breadcrumb-div">/</div>
            <a href="/brand" class="breadcrumb-link hidden-link">Door Brands</a>
            <div class="breadcrumb-div hidden-txt">/</div>
            <div class="breadcrumb-text">{{ $name }} Doors</div>
          </div>
        </div>
      </section>

      {{-- Door brand description (no window-collections sidebar on door pages) --}}
      <div class="w-layout-blockcontainer container-default w-container">
        <div class="mg-top-extra-large brands">
          <div class="inner-container _860px">
            <div class="div-block-52 brandmob">
              <h1 class="display-8 mid types">{{ $name }} Doors<span class="h1-subline">Installation &amp; Replacement</span></h1>
            </div>
            @if($description)
            <div class="rich-text-v2 mg-bottom--16px w-richtext">
              {!! $description !!}
            </div>
            @endif
          </div>
        </div>
        <div class="image-wrapper border-radius-image-default"></div>
      </div>

      @if($doorTypes->count() > 0)
      <div class="w-layout-blockcontainer container-default w-container">
        <div class="title-left---content-right">
          <h2 class="heading-20">{{ $doorsTitle }}</h2>
        </div>
        <div class="mg-top-large">
          <div class="collection-list-wrapper-21 w-dyn-list">
            <div role="list" class="grid-2-columns properties-grid---v1 collection-list w-dyn-items">
              @foreach($doorTypes as $dt)
              <div role="listitem" class="w-dyn-item">
                <a href="/door-types/{{ $dt['slug'] }}" class="property-wrapper-v1 w-inline-block">
                  <div class="property-card-top-content-v1">
                    <div class="image-wrapper border-radius-image-default property-card-top-content-v1---image">
                      @if($dt['image'])
                      <x-img :src="$dt['image']" preset="card" loading="eager" :alt="$dt['name']" class="image cover-image" />
                      @endif
                    </div>
                  </div>
                  <div class="property-card-bottom-content-v1">
                    <div><h3 class="display-5">{{ $dt['name'] }}</h3></div>
                  </div>
                </a>
              </div>
              @endforeach
            </div>
          </div>
        </div>
      </div>
      @endif

      <section class="section top-none">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="w-layout-grid grid-2-columns values-wrapper-grid">
            <div class="sticky-top static---tablet">
              <div class="inner-container _500px _100-tablet">
                <div class="inner-container _600px---tablet">
                  <div class="mg-top-default"><h2 class="heading-10">4 Easy Steps</h2></div>
                  <div class="mg-top-small">
                    <p>Our step-by-step process is designed to make replacing your windows and doors easy, stress-free, and fully tailored to your needs — from the first estimate to the final inspection.</p>
                  </div>
                  <div class="mg-top-default"><div class="buttons-row left"></div></div>
                </div>
              </div>
            </div>
            <div class="inner-container _592px _100-tablet">
              <div class="w-layout-grid grid-2-columns values-grid">
                <div class="value-wrapper">
                  <div class="image-wrapper"><img src="/webflow-assets/images/684d86f32d344f16ce6ec364_flag_24dp_E3E3E3_FILL0_wght400_GRAD0_opsz24.svg" loading="eager" alt="For-architects-deluxe-windows" class="image" /></div>
                  <div class="mg-top-small"><h3 class="display-5 mid">Start</h3></div>
                  <div class="mg-top-extra-small"><p class="paragraph-5">Looking to replace your windows and doors? Reach out to Deluxe Windows for a complimentary estimate.</p></div>
                </div>
                <div class="value-wrapper">
                  <div class="image-wrapper"><img src="/webflow-assets/images/684d86ff1fff20336f975d74_shopping_bag_24dp_E3E3E3_FILL0_wght400_GRAD0_opsz24.svg" loading="eager" alt="For-contractors-deluxe-windows" class="image" /></div>
                  <div class="mg-top-small"><h3 class="display-5 mid">Manufacture</h3></div>
                  <div class="mg-top-extra-small"><p class="paragraph-6">If you are satisfied with the provided estimate and approve it, we will order windows and doors according to your specifications and needs.</p></div>
                </div>
                <div class="value-wrapper">
                  <div class="image-wrapper"><img src="/webflow-assets/images/684d870c533c4f729eb8094c_settings_24dp_E3E3E3_FILL0_wght400_GRAD0_opsz24.svg" loading="eager" alt="For-property-managers-owners-deluxe-windows" class="image" /></div>
                  <div class="mg-top-small"><h3 class="display-5 mid">Remove and install</h3></div>
                  <div class="mg-top-extra-small"><p class="paragraph-7">Once the products are ready, we will arrange a convenient time for installation and ensure your new windows and doors are expertly fitted.</p></div>
                </div>
                <div class="value-wrapper">
                  <div class="image-wrapper"><img src="/webflow-assets/images/684d8718e99d2a34dfef7e4d_home_24dp_E3E3E3_FILL0_wght400_GRAD0_opsz24.svg" loading="eager" alt="For-property-managers-owners-deluxe-windows" class="image" /></div>
                  <div class="mg-top-small"><h3 class="display-5 mid">Final product</h3></div>
                  <div class="mg-top-extra-small"><p class="paragraph-7">Upon completion, each window and door will be thoroughly inspected to ensure they operate correctly and meet the highest standards of fit and finish.</p></div>
                </div>
                <div class="divider show-in-mbp"></div>
              </div>
            </div>
          </div>
        </div>
      </section>

      @include('partials.guarantee')

      @if($windowTypes->count() > 0)
      <section class="section top-none">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="title-left---content-right">
            <h2 class="heading-20">{{ $windowsTitle }}</h2>
          </div>
          <div class="mg-top-large">
            <div class="w-dyn-list">
              <div role="list" class="grid-2-columns properties-grid---v1 collection-list w-dyn-items">
                @foreach($windowTypes as $wt)
                <div role="listitem" class="w-dyn-item">
                  <a href="/window-type/{{ $wt['slug'] }}" class="property-wrapper-v1 w-inline-block">
                    <div class="property-card-top-content-v1">
                      <div class="image-wrapper border-radius-image-default property-card-top-content-v1---image">
                        @if($wt['image'])
                        <x-img :src="$wt['image']" preset="card" loading="eager" :alt="$wt['name']" class="image cover-image" />
                        @endif
                      </div>
                    </div>
                    <div class="property-card-bottom-content-v1">
                      <div><h3 class="display-5">{{ $wt['name'] }}</h3></div>
                    </div>
                  </a>
                </div>
                @endforeach
              </div>
            </div>
          </div>
        </div>
      </section>
      @endif

      @include('partials.cta')

      <section id="contact" class="section hero-v4 section-bg-none">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="w-layout-grid grid-2-columns contact-grid-v2">
            <div class="inner-container _440px _100-tablet">
              <div class="inner-container _550px---tablet">
                <h1>Contact Us</h1>
                <div class="mg-top-small"><p class="paragraph-8">We’re here to help with all your door and window needs.</p></div>
              </div>
              <div class="mg-top-default">
                <div class="w-layout-grid grid-2-columns contact-links-grid-v1">
                  <div class="contact-link---icon-left">
                    @include('partials.contact-phone-icon')
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
                <form id="email-form-2" name="email-form-2" data-name="Email Form 2" method="get" class="form-3" data-wf-page-id="6841ddf8ace3d9d9facb1583" aria-label="Email Form 2">
                  <div class="div-block-22">
                    <h2 class="display-4">Get Deluxe Windows for Less. {{ promotion_percent_label() }}* Windows</h2>
                    <label for="email-banner" class="body-14"><em class="italic-text">*Windows Replacement. Offer Expires </em><span class="date-span italic-span">{{ promotion_date('us-short') }}</span></label>
                    <label for="email-banner" class="body-14">Request a FREE No-Obligation Quote &amp; Expert Advice!</label>
                  </div>
                  <div class="div-block-23">
                    <div>
                      <label for="Name-2">Full name*</label>
                      <div class="input-wrapper">
                        <input class="input icon-left w-input" maxlength="256" name="Name" data-name="Name" placeholder="Full name" type="text" id="name" required="" />
                        <div class="input-line-icon-wrapper"><div class="filled-icons-font"></div></div>
                      </div>
                    </div>
                    <div class="div-block-30">
                      <label for="Email-2">Email address*</label>
                      <div class="input-wrapper">
                        <input class="input icon-left w-input" maxlength="256" name="Email" data-name="Email" placeholder="example@email.com" type="email" id="email" required="" />
                        <div class="input-line-icon-wrapper"><div class="filled-icons-font"></div></div>
                      </div>
                    </div>
                    <div>
                      <label for="Phone-2">Phone number*</label>
                      <div class="input-wrapper">
                        <input class="input icon-left w-input" maxlength="256" name="Phone" data-name="Phone" placeholder="{{ site_phone_display() }}" type="tel" id="phone" required="" />
                        <div class="input-line-icon-wrapper"><div class="filled-icons-font"></div></div>
                      </div>
                    </div>
                    <div>
                      <label for="Company">City</label>
                      <div class="input-wrapper">
                        <input class="input icon-left w-input" maxlength="256" name="Subject" data-name="Subject" placeholder="San Francisco" type="text" id="subject" required="" />
                        <div class="input-line-icon-wrapper">
                          <img loading="eager" src="/webflow-assets/images/6841ddf8ace3d9d9facb194d_star-icon-property-x-webflow-template.svg" alt="Star Icon - Property X Webflow Template" />
                        </div>
                      </div>
                    </div>
                    <div class="text-area-wrapper">
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

@endsection

@section('bodyScripts')
    <script src="/webflow-assets/js/jquery-3.5.1.min.js" type="text/javascript"></script>
    <script src="/webflow-assets/js/webflow-brands.js" type="text/javascript"></script>
@endsection

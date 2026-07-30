@php
  $fallbackDoors = collect([
    ['name' => 'Vinyl Doors', 'slug' => 'vinyl-doors', 'image' => '/webflow-assets/images/door-type-hero/vinyl-doors.jpg'],
    ['name' => 'Wood Clad Doors', 'slug' => 'wood-clad-doors', 'image' => '/webflow-assets/images/door-type-hero/wood-clad-doors.jpg'],
    ['name' => 'Fiberglass Doors', 'slug' => 'fiberglass-doors', 'image' => '/webflow-assets/images/door-type-hero/fiberglass-doors.jpg'],
    ['name' => 'Wood Doors', 'slug' => 'wood-doors', 'image' => '/webflow-assets/images/door-type-hero/wood-doors.jpg'],
    ['name' => 'Aluminum Doors', 'slug' => 'aluminum-doors', 'image' => '/webflow-assets/images/door-type-hero/aluminum-doors.jpg'],
    ['name' => 'Steel Doors', 'slug' => 'steel-doors', 'image' => '/webflow-assets/images/door-type-hero/steel-doors.jpg'],
  ]);

  $fallbackDoorsBySlug = $fallbackDoors->keyBy('slug');
  $landingDoors = collect($doors ?? [])
    ->map(function (array $door) use ($fallbackDoorsBySlug): array {
      $fallback = $fallbackDoorsBySlug->get((string) ($door['slug'] ?? ''), []);

      return [
        'name' => (string) ($door['name'] ?? $fallback['name'] ?? 'Door'),
        'slug' => (string) ($door['slug'] ?? $fallback['slug'] ?? ''),
        'image' => trim((string) ($door['image'] ?? '')) !== ''
          ? (string) $door['image']
          : (string) ($fallback['image'] ?? ''),
      ];
    })
    ->filter(fn (array $door): bool => $door['slug'] !== '')
    ->values();

  if ($landingDoors->isEmpty()) {
      $landingDoors = $fallbackDoors;
  }

  $doorBrandStripItems = [
    ['href' => '/door-brands/marvin', 'image' => '/webflow-assets/images/6915aaca08003de3e1e57018_marvin-logo-black.svg', 'alt' => 'Marvin doors'],
    ['href' => '/door-brands/milgard', 'image' => '/webflow-assets/images/6915aaea85f921adbca8a4e7_milgard.svg', 'alt' => 'Milgard doors'],
    ['href' => '/door-brands/jeld-wen', 'image' => '/webflow-assets/images/6915aa60264a3c99f69524c6_jv.svg', 'alt' => 'JELD-WEN doors'],
    ['href' => '/door-brands/anlin', 'image' => '/webflow-assets/images/6915c80af96503367881f15f_anlin2.svg', 'alt' => 'Anlin doors'],
    ['href' => '/door-brands/andersen', 'image' => '/webflow-assets/images/6915aaaa3027924fb18fb47c_andersen_logo_tm_rectangle_rgb.svg', 'alt' => 'Andersen doors'],
    ['href' => '/door-brands/ply-gem', 'image' => '/webflow-assets/images/6915aa80238022f9197f6973_pl.svg', 'alt' => 'Ply Gem doors'],
    ['href' => '/door-brands/simonton', 'image' => '/webflow-assets/images/6915aa3a24afaaa0a93dd455_Simonton_PrimaryLogo_Inline_RGB_Gradient_0822-1-2048x427.avif', 'alt' => 'Simonton doors'],
    ['href' => '/door-brands/alside', 'image' => '/webflow-assets/images/6915b29da8bcdcb16ec593b6_alside-logo.svg', 'alt' => 'Alside doors'],
    ['href' => '/door-brands/italwindows', 'image' => '/webflow-assets/images/6915bd3fcaf3c1f1ff04d9dd_italwindows.svg', 'alt' => 'Italwindows doors'],
    ['href' => '/door-brands/western-window-systems', 'image' => '/webflow-assets/images/6915b390bad100b6e6176ea7_westerngroup.svg', 'alt' => 'Western Window Systems doors'],
    ['href' => '/door-brands/all-weather-architectural-aluminum', 'image' => '/webflow-assets/images/6915bedcc5e0152198130ace_footer-logo__1__2-removebg-preview.avif', 'alt' => 'All Weather doors'],
  ];

  $doorFaq = [
    [
      'question' => 'How much does door replacement cost in the Bay Area?',
      'answer' => 'Installed cost depends on the opening, frame condition, material, glass, hardware, and finish work. Deluxe Windows measures the opening and provides a written quote before work begins.',
    ],
    [
      'question' => 'Do you install entry doors and patio doors?',
      'answer' => 'Yes. We install entry, sliding patio, French, multi-slide, bi-fold, and other residential door systems.',
    ],
    [
      'question' => 'Which door material is best for my home?',
      'answer' => 'Fiberglass and vinyl are popular for low maintenance, while clad wood, wood, steel, and aluminum offer different architectural and performance benefits. We recommend options after reviewing the opening and exposure.',
    ],
    [
      'question' => 'Can you replace a door without changing the opening?',
      'answer' => 'Often, yes. A like-for-like replacement can preserve the existing opening. If the frame is damaged or you want a larger opening, the additional work will be included in the proposal.',
    ],
    [
      'question' => 'How long does door installation take?',
      'answer' => 'Many straightforward replacements are completed in a day. Larger multi-panel systems, structural changes, and custom finish work require more time.',
    ],
  ];

  $doorPromotionName = str_ireplace(
    ['Windows and Doors', 'Windows & Doors'],
    'Doors',
    rtrim(trim(promotion_name()), '.')
  );
  $doorPromotionPercent = app(\App\Services\PromotionControlService::class)->globalDiscountPercent().'%';
  $doorSaleHeadlineHtml = '<h2 class="display-4">Get Deluxe Doors for Less.'
    .($doorPromotionName !== '' ? ' <br>'.e($doorPromotionName).'.' : '')
    .' <br>'.e($doorPromotionPercent).'&nbsp;OFF*</h2>';
@endphp

@include('partials.hero', [
  'doorHero' => true,
  'doorLandingHero' => true,
  'brandHeroFormHtml' => '',
  'pagePromotionAvailable' => false,
  'saleHeadlineHtmlOverride' => $doorSaleHeadlineHtml,
  'slug' => 'doors',
])

@include('partials.trust-badges')

<section class="section_breadcrumbs section-121">
  <div class="w-layout-blockcontainer container-default breadcrumbs-container w-container">
    <div class="breadcrumbs-wrapper">
      <a href="/" class="breadcrumb-link">Home</a>
      <div class="breadcrumb-div">/</div>
      <div class="breadcrumb-text">Doors</div>
    </div>
  </div>
</section>

<section class="section hero-v4 page-intro-hero">
  <div class="w-layout-blockcontainer container-default w-container">
    <div class="w-layout-vflex inner-container _500px---mbl center">
      <div class="mg-top-small">
        <h1 class="display-10 mid text-light">Door Replacement &amp; Installation<br />for Bay Area Homes</h1>
      </div>
    </div>
    <div class="mg-top-small">
      <div class="inner-container _690px center">
        <div class="text-neutral-light">
          <p class="paragraph-26">
            Compare entry, patio, French, sliding, and multi-panel doors from leading manufacturers.
            One local team helps you choose the right product and installs it correctly.
          </p>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="w-layout-blockcontainer container-default w-container">
    <div class="text-center---mbl">
      <div class="title-left---content-right">
        <div class="width-100-mobile-landscape">
          <h2 class="display-8 mid">Choose the Right Door Material</h2>
        </div>
      </div>
    </div>
    <div class="mg-top-large">
      <div class="collection-list-wrapper-2 w-dyn-list">
        <div role="list" class="grid-2-columns properties-grid---v1 w-dyn-items">
          @foreach($landingDoors as $door)
            @include('partials.doors-index-card', ['door' => $door])
          @endforeach
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section top-none">
  <div class="w-layout-blockcontainer container-default w-container">
    <div class="w-layout-grid grid-2-columns values-wrapper-grid">
      <div class="image-wrapper border-radius-image-default door-landing-site-image">
        <x-img
          src="/webflow-assets/images/6979dd17e1e8dbc951d9753e_Interior-Kitchen-Hero-Doors-MR-scaled.webp"
          preset="hero_bg"
          loading="lazy"
          alt="Black patio doors connecting a kitchen with the outdoors"
          class="image cover-image"
        />
      </div>
      <div class="inner-container _500px _100-tablet door-landing-site-copy">
        <div class="mg-top-default">
          <h2 class="heading-8">One Team for Every Door Opening</h2>
        </div>
        <div class="mg-top-small">
          <p class="paragraph-17">
            A front entry and a multi-slide patio system solve different problems. We compare security,
            weather exposure, daily operation, glass, hardware, and style before recommending a product.
          </p>
        </div>
        <div class="mg-top-default w-richtext">
          <h3>Entry doors</h3>
          <p>Secure hardware, weather protection, and a stronger first impression.</p>
          <h3>Sliding and French doors</h3>
          <p>Comfortable access, efficient glass, and dependable daily operation.</p>
          <h3>Multi-slide and bi-fold systems</h3>
          <p>Large openings planned around the home, structure, and the way you entertain.</p>
        </div>
        <div class="mg-top-default">
          <a href="#wf-form-Main-Form" class="primary-button w-inline-block">
            <div class="text-block-22">Discuss Your Door Project</div>
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section top-none">
  <div class="w-layout-blockcontainer container-default w-container">
    @include('partials.brand-strip', [
      'items' => $doorBrandStripItems,
      'title' => 'Explore Leading Door Brands',
      'variant' => 'static',
    ])
  </div>
</section>

<section class="section top-none">
  <div class="w-layout-blockcontainer container-default w-container">
    <div class="w-layout-grid grid-2-columns values-wrapper-grid">
      <div class="sticky-top static---tablet">
        <div class="inner-container _500px _100-tablet">
          <div class="inner-container _600px---tablet">
            <div class="mg-top-default"><h2 class="heading-8">4 Easy Steps</h2></div>
            <div class="mg-top-small">
              <p>
                A clear process from the first measurement to the final operation check,
                with one team responsible for the complete door replacement.
              </p>
            </div>
            <div class="mg-top-default">
              <a href="#wf-form-Main-Form" class="primary-button w-inline-block">
                <div class="text-block-22">Request a Free Estimate</div>
              </a>
            </div>
          </div>
        </div>
      </div>
      <div class="inner-container _592px _100-tablet">
        <div class="w-layout-grid grid-2-columns values-grid">
          <div class="value-wrapper">
            <div class="image-wrapper">
              <img src="/webflow-assets/images/684d86f32d344f16ce6ec364_flag_24dp_E3E3E3_FILL0_wght400_GRAD0_opsz24.svg" loading="lazy" alt="" class="image" />
            </div>
            <div class="mg-top-small"><h3 class="display-5 mid">Measure</h3></div>
            <div class="mg-top-extra-small"><p class="paragraph-5">We inspect the opening, frame, exposure, and finish conditions.</p></div>
          </div>
          <div class="value-wrapper">
            <div class="image-wrapper">
              <img src="/webflow-assets/images/684d86ff1fff20336f975d74_shopping_bag_24dp_E3E3E3_FILL0_wght400_GRAD0_opsz24.svg" loading="lazy" alt="" class="image" />
            </div>
            <div class="mg-top-small"><h3 class="display-5 mid">Choose</h3></div>
            <div class="mg-top-extra-small"><p class="paragraph-6">Compare suitable materials, brands, glass, finishes, and hardware.</p></div>
          </div>
          <div class="value-wrapper">
            <div class="image-wrapper">
              <img src="/webflow-assets/images/684d870c533c4f729eb8094c_settings_24dp_E3E3E3_FILL0_wght400_GRAD0_opsz24.svg" loading="lazy" alt="" class="image" />
            </div>
            <div class="mg-top-small"><h3 class="display-5 mid">Install</h3></div>
            <div class="mg-top-extra-small"><p class="paragraph-7">Our team removes the old unit and installs the new door system.</p></div>
          </div>
          <div class="value-wrapper">
            <div class="image-wrapper">
              <img src="/webflow-assets/images/684d8718e99d2a34dfef7e4d_home_24dp_E3E3E3_FILL0_wght400_GRAD0_opsz24.svg" loading="lazy" alt="" class="image" />
            </div>
            <div class="mg-top-small"><h3 class="display-5 mid">Inspect</h3></div>
            <div class="mg-top-extra-small"><p class="paragraph-7">We check operation, fit, finish, and clean the work area.</p></div>
          </div>
          <div class="divider show-in-mbp"></div>
        </div>
      </div>
    </div>
  </div>
</section>

@include('partials.reviews')

@include('partials.guarantee', ['guaranteeContext' => 'doors'])

@include('partials.certifications')

@include('partials.for-professionals', ['professionalsContext' => 'doors'])

<section class="section top-none page-metadata-faq" aria-labelledby="doors-landing-faq-heading">
  <div class="w-layout-blockcontainer container-default w-container">
    <div class="w-layout-grid grid-2-columns faqs-grid-v3">
      <div class="sticky-top static---mbl">
        <div class="inner-container _450px---mbl">
          <div class="inner-container _275px---tablet _100-mbl">
            <div class="inner-container _340px _100-mbl">
              <div class="mg-top-small">
                <h2 id="doors-landing-faq-heading" class="heading-44">Door Replacement Questions</h2>
              </div>
              <div class="div-block-49">
                <p class="paragraph-2">
                  Call us at
                  <a href="tel:{{ site_phone_tel() }}" data-phone-source="doors-landing-faq">{{ site_phone_display() }}</a>
                  to discuss your opening.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="inner-container _763px width-100">
        <div class="card accordion-card v2">
          <div class="w-layout-grid grid-1-column accordion-v6">
            @foreach($doorFaq as $item)
              <details class="accordion-item-wrapper v2{{ $loop->first ? ' first' : '' }}{{ $loop->last ? ' last' : '' }}">
                <summary class="accordion-top">
                  <span class="text-titles"><span class="faqs-title">{{ $item['question'] }}</span></span>
                  <span class="accordion-icon-wrapper" aria-hidden="true">
                    <span class="accordion-icon-line vertical"></span>
                    <span class="accordion-icon-line"></span>
                  </span>
                </summary>
                <div class="accordion-bottom v1">
                  <p class="accordion-paragraph">{{ $item['answer'] }}</p>
                </div>
              </details>
            @endforeach
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<div id="door-contact">
  @include('partials.cta', [
    'ctaHref' => '#wf-form-Main-Form',
    'ctaTitleLine1' => 'The Right Door',
    'ctaTitleLine2' => 'Starts with the Right Fit',
    'ctaText' => 'Tell us about the opening. We will help compare products and prepare a clear estimate.',
    'ctaButtonLabel' => 'Get a Free Door Estimate',
    'ctaImage' => '/webflow-assets/images/688efd96309fed3347c95d0b_trustile-traditional-style-black-front-door-1.jpg',
    'ctaImageAlt' => 'Black entry door installed in a traditional home',
    'ctaImageClass' => 'cover-image',
  ])
</div>

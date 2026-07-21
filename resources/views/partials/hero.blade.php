@php
  $typeHero = !empty($windowTypeHero) || !empty($doorTypeHero);
  $brandLikeHero = !empty($brandHero) || $typeHero || !empty($collectionHero) || !empty($doorHero);
  $heroPageId = !empty($collectionHero)
    ? '69366119c296b5e2e8bdbfb8'
    : ($typeHero
      ? '688e50676f1dbd8cba0e091a'
      : (!empty($brandHero)
        ? '6841ddf8ace3d9d9facb1583'
        : (!empty($doorHero)
          ? '6841ddf8ace3d9d9facb156f'
          : (!empty($windowHeroImage) ? '6841ddf8ace3d9d9facb1582' : '6841df5688ca2f74fd53ec90'))));
  $heroElementId = !empty($collectionHero)
    ? '89b9e427-c9d3-6d8b-0afb-075923310b6c'
    : ($typeHero
      ? '3ab01c22-18de-4545-ffef-5a89d31afac2'
      : (!empty($brandHero)
        ? 'dc04ee7a-918f-7eb9-bff0-f9899431c4c3'
        : (!empty($doorHero)
          ? '553713b0-acde-64e1-6dce-d76cee8c0ff6'
          : (!empty($windowHeroImage) ? '0d2c5edc-6a74-d360-f6d2-0a02682efe78' : 'c3765d23-1eba-01a8-993c-c59200a6f722'))));
  $heroEmailNode = !empty($collectionHero)
    ? 'w-node-_89b9e427-c9d3-6d8b-0afb-075923310b7e-e8bdbfb8'
    : ($typeHero
      ? 'w-node-_3ab01c22-18de-4545-ffef-5a89d31afad4-ba0e091a'
      : (!empty($brandHero)
        ? 'w-node-dc04ee7a-918f-7eb9-bff0-f9899431c4d5-facb1583'
        : (!empty($doorHero)
          ? 'w-node-_553713b0-acde-64e1-6dce-d76cee8c1008-facb156f'
          : (!empty($windowHeroImage) ? 'w-node-_0d2c5edc-6a74-d360-f6d2-0a02682efe8a-facb1582' : 'w-node-c3765d23-1eba-01a8-993c-c59200a6f734-fd53ec90'))));
  $heroPhoneNode = !empty($collectionHero)
    ? 'w-node-_89b9e427-c9d3-6d8b-0afb-075923310b86-e8bdbfb8'
    : ($typeHero
      ? 'w-node-_3ab01c22-18de-4545-ffef-5a89d31afadc-ba0e091a'
      : (!empty($brandHero)
        ? 'w-node-dc04ee7a-918f-7eb9-bff0-f9899431c4dd-facb1583'
        : (!empty($doorHero)
          ? 'w-node-_553713b0-acde-64e1-6dce-d76cee8c1010-facb156f'
          : (!empty($windowHeroImage) ? 'w-node-_0d2c5edc-6a74-d360-f6d2-0a02682efe92-facb1582' : 'w-node-c3765d23-1eba-01a8-993c-c59200a6f73c-fd53ec90'))));
  $heroCityNode = !empty($collectionHero)
    ? 'w-node-_89b9e427-c9d3-6d8b-0afb-075923310b8e-e8bdbfb8'
    : ($typeHero
      ? 'w-node-_3ab01c22-18de-4545-ffef-5a89d31afae4-ba0e091a'
      : (!empty($brandHero)
        ? 'w-node-dc04ee7a-918f-7eb9-bff0-f9899431c4e5-facb1583'
        : (!empty($doorHero)
          ? 'w-node-_553713b0-acde-64e1-6dce-d76cee8c1018-facb156f'
          : (!empty($windowHeroImage) ? 'w-node-_0d2c5edc-6a74-d360-f6d2-0a02682efe9a-facb1582' : 'w-node-c3765d23-1eba-01a8-993c-c59200a6f744-fd53ec90'))));
  $heroMessageNode = !empty($collectionHero)
    ? 'w-node-_89b9e427-c9d3-6d8b-0afb-075923310b95-e8bdbfb8'
    : ($typeHero
      ? 'w-node-_3ab01c22-18de-4545-ffef-5a89d31afaeb-ba0e091a'
      : (!empty($brandHero)
        ? 'w-node-dc04ee7a-918f-7eb9-bff0-f9899431c4ec-facb1583'
        : (!empty($doorHero)
          ? 'w-node-_553713b0-acde-64e1-6dce-d76cee8c101f-facb156f'
          : (!empty($windowHeroImage) ? 'w-node-_0d2c5edc-6a74-d360-f6d2-0a02682efea1-facb1582' : 'w-node-c3765d23-1eba-01a8-993c-c59200a6f74b-fd53ec90'))));
  $heroPricingBlockClass = !empty($collectionHero)
    ? 'rich-text-block-3'
    : ($typeHero ? 'rich-text-block-7' : 'rich-text-block-5');
  $heroPricingHtml = !empty($collectionHero)
    ? ($heroFormHtml ?? app(\App\Services\PromotionControlService::class)->priceHtml('915', '$549'))
    : ($typeHero
      ? ($heroFormHtml ?? '<p>Starting from $1199 per window installed.</p><p><strong>Special pricing available upon request! </strong>‍</p>')
      : ($brandHeroFormHtml ?? '<p>Starting from $999 per window installed.</p><p><strong>Special pricing available upon request!</strong>‍</p>'));
  // Prefer the HTML actually rendered in the desktop form (window/door material pages).
  $heroPromoSourceHtml = $windowDiscountHtml ?? $doorDiscountHtml ?? $heroPricingHtml;
  $heroMobilePriceTagHtml = promotion_hero_mobile_price_tag_html(
    $heroPromoSourceHtml,
    !empty($collectionHero),
    $typeHero,
    $brandPromotionPricing ?? $heroPromotionPricing ?? null,
  );
  $hasSpecificPagePromotion = !empty($pagePromotionAvailable)
    || !empty($brandPromotionPricing ?? null)
    || !empty($heroPromotionPricing ?? null)
    || str_contains((string) $heroPromoSourceHtml, 'hero-promo-priced')
    || str_contains((string) $heroPromoSourceHtml, '<s>');
  // Hide the hero promo/price block entirely when a page opts in and has no price
  // (e.g. door-brand pages where no linked door has a Promotions price).
  $hideHeroPricing = !empty($hideHeroPromoWhenEmpty) && empty($brandHeroFormHtml ?? null);
  $windowTypeHeroCopy = [
    'vinyl-windows' => [
      'headline' => 'Upgrade to Energy Efficient Vinyl Windows for Less',
      'description' => 'Low-maintenance comfort, strong insulation, and clean style at a budget-friendly price.',
    ],
    'wood-clad-windows' => [
      'headline' => 'Upgrade to Energy Efficient Wood-Clad Windows for Less',
      'description' => 'Warm wood interior beauty with weather-resistant exterior performance and better savings.',
    ],
    'fiberglass-windows' => [
      'headline' => 'Upgrade to Energy Efficient Fiberglass Windows for Less',
      'description' => 'Engineered strength, stable performance, and year-round efficiency with lower operating cost.',
    ],
    'wood-windows' => [
      'headline' => 'Upgrade to Energy Efficient Wood Windows for Less',
      'description' => 'Timeless natural character with improved efficiency for comfort and long-term value.',
    ],
    'aluminum-windows' => [
      'headline' => 'Upgrade to Energy Efficient Aluminum Windows for Less',
      'description' => 'Modern slim frames, crisp lines, and durable performance with practical energy savings.',
    ],
    'aluminum-clad-windows' => [
      'headline' => 'Upgrade to Energy Efficient Aluminum-Clad Windows for Less',
      'description' => 'Hybrid wood-and-metal construction that balances premium style, protection, and value.',
    ],
    'steel-windows' => [
      'headline' => 'Upgrade to Energy Efficient Steel Windows for Less',
      'description' => 'Sleek architectural look, exceptional durability, and efficient comfort at a better price.',
    ],
  ];
  $doorTypeHeroCopy = [
    'vinyl-doors' => [
      'headline' => 'Upgrade to Energy Efficient Vinyl Doors for Less',
      'description' => 'Low-maintenance door systems with practical insulation, clean style, and strong value.',
    ],
    'wood-clad-doors' => [
      'headline' => 'Upgrade to Energy Efficient Wood-Clad Doors for Less',
      'description' => 'Warm wood character inside with durable exterior protection for Bay Area homes.',
    ],
    'fiberglass-doors' => [
      'headline' => 'Upgrade to Energy Efficient Fiberglass Doors for Less',
      'description' => 'Weather-resistant strength, insulated comfort, and low-maintenance curb appeal.',
    ],
    'wood-doors' => [
      'headline' => 'Upgrade to Energy Efficient Wood Doors for Less',
      'description' => 'Timeless natural grain, solid construction, and better comfort at the entry.',
    ],
    'aluminum-doors' => [
      'headline' => 'Upgrade to Energy Efficient Aluminum Doors for Less',
      'description' => 'Slim modern profiles, durable finishes, and bright indoor-outdoor living.',
    ],
    'steel-doors' => [
      'headline' => 'Upgrade to Energy Efficient Steel Doors for Less',
      'description' => 'Secure insulated entry doors built for strength, comfort, and long-term value.',
    ],
  ];
  $materialTypeSlug = isset($slug) ? strtolower((string) $slug) : '';
  if ($materialTypeSlug === '' && isset($windowFieldData['slug'])) {
    $materialTypeSlug = strtolower((string) $windowFieldData['slug']);
  }
  // door-types slugs are brand+material (e.g. milgard-vinyl-doors) — map to material key.
  if (!empty($doorTypeHero)) {
    $doorMaterialSlug = null;
    foreach (['wood-clad', 'fiberglass', 'aluminum', 'aluminium', 'vinyl', 'steel', 'wood'] as $material) {
      if (str_contains($materialTypeSlug, $material)) {
        $normalized = $material === 'aluminium' ? 'aluminum' : $material;
        $doorMaterialSlug = $normalized.'-doors';
        break;
      }
    }
    $materialTypeSlug = $doorMaterialSlug ?? '';
  }
  $isWindowsMaterialHero = !empty($windowTypeHero) || !empty($windowHeroImage);
  $isDoorsMaterialHero = !empty($doorHero) || !empty($doorHeroImage) || !empty($doorTypeHero);
  $heroMaterialCopy = $isDoorsMaterialHero
    ? ($doorTypeHeroCopy[$materialTypeSlug] ?? null)
    : ($windowTypeHeroCopy[$materialTypeSlug] ?? null);
  $heroHeadlineText = $isWindowsMaterialHero
    ? (($heroMaterialCopy['headline'] ?? null) ?: 'Upgrade to Energy Efficient Windows for Less')
    : (!empty($doorTypeHero)
      ? (($heroMaterialCopy['headline'] ?? null) ?: 'Upgrade to Energy Efficient Doors for Less')
      : ($isDoorsMaterialHero
        ? (($heroMaterialCopy['headline'] ?? null) ?: 'Upgrade to Energy Efficient Doors for Less')
        : ((!empty($doorBrandHero) && trim((string) ($brandName ?? '')) !== '')
          ? 'Upgrade to Energy Efficient '.trim((string) $brandName).' Doors for Less'
          : ((!empty($windowBrandHero) && trim((string) ($brandName ?? '')) !== '')
            ? 'Upgrade to Energy Efficient '.trim((string) $brandName).' Windows for Less'
            : 'Upgrade to Energy Efficient Windows and Doors for Less'))));
  $heroMiniDescription = $isWindowsMaterialHero || $isDoorsMaterialHero
    ? (($heroMaterialCopy['description'] ?? null) ?: ($isDoorsMaterialHero
      ? 'Get secure, stylish door solutions with better comfort and pricing for your home.'
      : 'Get high-performance window solutions with better comfort and pricing for your home.'))
    : ((!empty($doorBrandHero) && trim((string) ($brandName ?? '')) !== '')
      ? 'Authorized '.trim((string) $brandName).' door installation across the Bay Area — entry, patio, and sliding.'
      : ((!empty($windowBrandHero) && trim((string) ($brandName ?? '')) !== '')
        ? 'Authorized '.trim((string) $brandName).' window installation across the Bay Area — vinyl, fiberglass, wood & more.'
        : ''));
@endphp

      <div class="div-block-59">
        @if(!empty($heroBackgroundImage))
          {{-- Static hero background (brand pages etc.) --}}
          <div data-parallax="1" style="background-image:url('{{ thumbnail_url($heroBackgroundImage, 'hero_bg') }}');background-position:center center;background-size:cover;background-repeat:no-repeat;" class="div-block-61"></div>
        @elseif(!empty($doorHeroImage))
          {{-- Doors detail page: featured image as background --}}
          <div data-parallax="1" style="background-image:url('{{ thumbnail_url($doorHeroImage, 'hero_bg') }}');background-position:center center;background-size:cover;background-repeat:no-repeat;" class="div-block-61"></div>
        @elseif(!empty($windowHeroImage))
          {{-- Windows detail page: static product image as background --}}
          <div data-parallax="1" style="background-image:url('{{ thumbnail_url($windowHeroImage, 'hero_bg') }}');background-position:center center;background-size:cover;background-repeat:no-repeat;" class="div-block-61"></div>
        @elseif($brandLikeHero || !empty($doorHero))
          {{-- Fallback for hero-based templates without image: solid blue background only --}}
          <div class="div-block-61"></div>
        @else
          {{-- Homepage: static image first (LCP), the 25 MB video is appended
               on desktop only and covers the image once it starts playing. --}}
        <div class="code-embed-5 w-embed w-script">
          <div id="hero-bg-wrapper" class="video-bg-container">
            <img
              src="{{ thumbnail_url('/webflow-assets/images/69ce36fd76a6aaff9c68df7e_01.webp', 'hero_mobile') }}"
              srcset="{{ thumbnail_url('/webflow-assets/images/69ce36fd76a6aaff9c68df7e_01.webp', 'hero_mobile') }} 768w, {{ thumbnail_url('/webflow-assets/images/69ce36fd76a6aaff9c68df7e_01.webp', 'hero_bg') }} 1920w"
              sizes="100vw"
              alt=""
              fetchpriority="high"
              decoding="async"
              class="hero-bg-image"
            />
          </div>

          <script>
            (function () {
              if (window.innerWidth <= 767) return;
              var wrapper = document.getElementById("hero-bg-wrapper");
              var video = document.createElement("video");
              video.autoplay = true;
              video.loop = true;
              video.muted = true;
              video.setAttribute("playsinline", "");
              var source = document.createElement("source");
              source.src = "/webflow-assets/videos/687ca10e41cc245f5cdacfd5_0719_2-copy.mp4";
              source.type = "video/mp4";
              video.appendChild(source);
              wrapper.appendChild(video);
            })();
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
                      {{-- Promo line in hero; canonical H1 lives in the product content section from SEO --}}
                      <h2 class="heading-49">{{ $heroHeadlineText }}</h2>
                      @if($heroMiniDescription !== '')
                        <p class="hero-mini-description">{{ $heroMiniDescription }}</p>
                      @endif
                      <p>‍</p>
                      <div class="w-embed">
                        <h2 data-city="">Bay Area Installers</h2>
                      </div>
                    </div>
                    <div class="hero-mobile-promo-slot hero-mobile-promo-slot--mobile">
                      @include('partials.hero-mobile-promo', [
                        'variant' => $hasSpecificPagePromotion ? 'price' : 'percent',
                        'badgeHtml' => $heroMobilePriceTagHtml,
                        'buttonLabel' => 'Get Free Quote',
                      ])
                    </div>
                    @if($brandLikeHero || !empty($doorHero))
                    </div>
                    @endif
                    @else
                    <h1 class="heading-4">Looking to Replace Your Windows in the Bay Area?</h1>
                    <div class="hero-mobile-promo-slot hero-mobile-promo-slot--mobile">
                      @include('partials.hero-mobile-promo', [
                        'buttonLabel' => 'Request a Free Estimate',
                      ])
                    </div>
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
                    <x-img :src="$brandLogo" preset="logo" alt="" loading="lazy" class="svg50 sidebar-svg top-svg" />
                    @endif
                    @if(!$hideHeroPricing)
                    <label for="email-banner" class="body-14"></label>
                    <div data-estimate-form-promo data-page-promotion="{{ $hasSpecificPagePromotion ? 'specific' : 'global' }}" class="estimate-form-promo promo-offer-context--form">
                      <div class="{{ $heroPricingBlockClass }} w-richtext">
                        {!! $heroPricingHtml !!}
                      </div>
                    </div>
                    @endif
                    @elseif(!empty($doorHero) && !empty($doorDiscountHtml))
                    <label for="email-banner" class="body-14"></label>
                    <div data-estimate-form-promo data-page-promotion="{{ $hasSpecificPagePromotion ? 'specific' : 'global' }}" class="estimate-form-promo promo-offer-context--form">
                      <div class="rich-text-block-6 w-richtext">
                        {!! $doorDiscountHtml !!}
                      </div>
                    </div>
                    @elseif(!empty($windowHeroImage) && !empty($windowDiscountHtml))
                    <label for="email-banner" class="body-14"></label>
                    <div data-estimate-form-promo data-page-promotion="{{ $hasSpecificPagePromotion ? 'specific' : 'global' }}" class="estimate-form-promo promo-offer-context--form">
                      <div class="rich-text-block-4 w-richtext">
                        {!! $windowDiscountHtml !!}
                      </div>
                    </div>
                    @else
                    <div data-estimate-form-promo data-page-promotion="global" class="estimate-form-promo promo-offer-context--form hero-mobile-promo-slot hero-mobile-promo-slot--form">
                      {!! promotion_home_html() !!}
                    </div>
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
                          placeholder="{{ site_phone_display() }}"
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
                    ><span class="date-span italic-span"
                      ><em class="italic-text">{{ promotion_date('us-short') }}</em></span
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
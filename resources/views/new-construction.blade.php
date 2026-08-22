@extends('layouts.classic')

@section('bodyClass', 'body-18 height-auto new-construction-page')

@section('head')
  @php
    $ncCss = public_path('webflow-overrides/new-construction.css');
    $ncCssVersion = is_file($ncCss) ? (string) filemtime($ncCss) : '1';
  @endphp
  <link href="/webflow-overrides/new-construction.css?v={{ $ncCssVersion }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
@php
  $ncPromotionName = rtrim(trim(promotion_name()), '.');
  $ncPromotionPercent = app(\App\Services\PromotionControlService::class)->globalDiscountPercent().'%';
  $ncSaleHeadlineHtml = '<h2 class="display-4">New Construction Packages for Less.'
    .($ncPromotionName !== '' ? ' <br>'.e($ncPromotionName).'.' : '')
    .' <br>'.e($ncPromotionPercent).'&nbsp;OFF*</h2>';

  $ncProducts = [
    [
      'name' => 'Vinyl Windows',
      'href' => '/windows/vinyl-windows',
      'image' => '/webflow-assets/images/new-construction/vinyl-windows.avif',
      'description' => 'Budget-friendly, low-maintenance frames that meet energy code in every room of the build.',
    ],
    [
      'name' => 'Fiberglass Windows',
      'href' => '/windows/fiberglass-windows',
      'image' => '/webflow-assets/images/new-construction/fiberglass-windows.avif',
      'description' => 'Dimensionally stable frames that handle sun exposure and large openings without warping.',
    ],
    [
      'name' => 'Aluminum Windows',
      'href' => '/windows/aluminum-windows',
      'image' => '/webflow-assets/images/new-construction/aluminum-windows.avif',
      'description' => 'Slim sightlines and big glass for modern architecture and floor-to-ceiling designs.',
    ],
    [
      'name' => 'Entry Doors',
      'href' => '/doors/steel-doors',
      'image' => '/webflow-assets/images/new-construction/entry-doors.avif',
      'description' => 'Secure, insulated front doors that set the first impression of the new home.',
    ],
    [
      'name' => 'Sliding & French Patio Doors',
      'href' => '/doors/fiberglass-doors',
      'image' => '/webflow-assets/images/new-construction/patio-doors.avif',
      'description' => 'Smooth-operating patio systems planned into the framing from day one.',
    ],
    [
      'name' => 'Multi-Slide & Bi-Fold Systems',
      'href' => '/doors/aluminum-doors',
      'image' => '/webflow-assets/images/new-construction/multislide-doors.avif',
      'description' => 'Moving glass walls for indoor-outdoor living — sized and engineered for your plans.',
    ],
  ];

  $ncBenefits = [
    [
      'icon' => '/webflow-assets/images/new-construction/icons/frames.svg',
      'title' => 'New-Construction Frames',
      'text' => 'Nail-fin and block-frame units installed before stucco or siding — flashed and sealed the right way, not retrofitted later.',
    ],
    [
      'icon' => '/webflow-assets/images/new-construction/icons/energy.svg',
      'title' => 'Title 24 & ENERGY STAR',
      'text' => 'Products selected to pass California energy code and inspection the first time, with documentation for your permit set.',
    ],
    [
      'icon' => '/webflow-assets/images/new-construction/icons/pricing.svg',
      'title' => 'Whole-House Pricing',
      'text' => 'One quote for every opening in the project. Volume pricing on multi-window packages beats ordering piece by piece.',
    ],
    [
      'icon' => '/webflow-assets/images/new-construction/icons/schedule.svg',
      'title' => 'Built Around Your Schedule',
      'text' => 'We coordinate delivery and installation with your GC and framing timeline, so windows never hold up the build.',
    ],
  ];
@endphp

@include('partials.hero', [
  'doorHero' => true,
  'landingHeroBgClass' => 'new-construction-hero-bg',
  'brandHeroFormHtml' => '',
  'pagePromotionAvailable' => false,
  'saleHeadlineHtmlOverride' => $ncSaleHeadlineHtml,
  'heroHeadlineOverride' => 'Building a New Home? Get Every Window & Door from One Team',
  'heroMiniDescriptionOverride' => 'New-construction windows and doors for <span data-area-label>Bay Area</span> homes — specified from your plans, priced for the whole house, and installed on your build schedule.',
  'slug' => 'new-construction',
])

@include('partials.trust-badges')

<section class="section_breadcrumbs section-121">
  <div class="w-layout-blockcontainer container-default breadcrumbs-container w-container">
    <div class="breadcrumbs-wrapper">
      <a href="/" class="breadcrumb-link">Home</a>
      <div class="breadcrumb-div">/</div>
      <div class="breadcrumb-text">New Construction</div>
    </div>
  </div>
</section>

<section class="section hero-v4 page-intro-hero">
  <div class="w-layout-blockcontainer container-default w-container">
    <div class="w-layout-vflex inner-container _500px---mbl center">
      <div class="mg-top-small">
        <h1 class="display-10 mid text-light">New Construction Windows &amp; Doors<br />for <span data-area-label>Bay Area</span> Homes</h1>
      </div>
    </div>
    <div class="mg-top-small">
      <div class="inner-container _690px center">
        <div class="text-neutral-light">
          <p class="paragraph-26">
            Building from the ground up or adding an ADU in the <span data-area-label>Bay Area</span>?
            We supply and install new-construction windows, patio doors, and entry doors from leading
            manufacturers — one takeoff, one delivery, one accountable installation team.
          </p>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section nc-benefits-section">
  <div class="w-layout-blockcontainer container-default w-container">
    <div class="text-center---mbl">
      <div class="title-left---content-right">
        <div class="width-100-mobile-landscape">
          <h2 class="display-8 mid">Why Builders &amp; Homeowners Start Here</h2>
        </div>
      </div>
    </div>
    <div class="mg-top-large">
      <div class="nc-benefits-grid" role="list">
        @foreach($ncBenefits as $benefit)
          <article class="nc-benefit-card" role="listitem">
            <div class="nc-benefit-icon">
              <img src="{{ $benefit['icon'] }}" loading="lazy" alt="" />
            </div>
            <h3>{{ $benefit['title'] }}</h3>
            <p>{{ $benefit['text'] }}</p>
          </article>
        @endforeach
      </div>
    </div>
  </div>
</section>

@include('partials.before-after-slider', [
  'beforeSrc' => '/webflow-assets/images/new-construction/before-no-windows.avif',
  'afterSrc' => '/webflow-assets/images/new-construction/after-with-windows.avif',
  'beforeAlt' => 'New construction home with framed openings boarded with plywood — windows not installed yet',
  'afterAlt' => 'Same new construction home with vinyl windows, entry door, and garage door installed',
  'compareHeading' => 'From Framed Openings to Finished Home',
  'compareText' => 'Drag the slider to compare a new build before window installation with the finished result — every opening specified, delivered, and installed by one team.',
  'beforeLabel' => 'Before',
  'afterLabel' => 'After',
])

<section class="section top-none nc-process-section">
  <div class="w-layout-blockcontainer container-default w-container">
    <div class="w-layout-grid grid-2-columns values-wrapper-grid nc-process-shell">
      <div class="image-wrapper border-radius-image-default nc-process-image">
        <x-img
          src="/webflow-assets/images/new-construction/process.avif"
          preset="hero_bg"
          loading="lazy"
          alt="New home kitchen with floor-to-ceiling glass doors opening to the backyard"
          class="image cover-image"
        />
        <div class="nc-process-image-note">
          <strong>Plans in, quote out.</strong>
          <span>Send us your window schedule or floor plans — we return a complete priced takeoff.</span>
        </div>
      </div>
      <div class="inner-container _500px _100-tablet nc-process-copy">
        <div class="nc-process-kicker">From plans to final walkthrough</div>
        <div class="nc-process-heading">
          <h2 class="heading-8">How New Construction Projects Work</h2>
        </div>
        <div class="nc-process-intro">
          <p class="paragraph-17">
            A new build is not a retrofit. Openings are framed to the product, energy code is
            checked on paper, and delivery has to match the construction calendar. Here is the
            process we run on every project.
          </p>
        </div>
        <div class="nc-process-list" role="list">
          <article class="nc-process-item" role="listitem">
            <span class="nc-process-number" aria-hidden="true">01</span>
            <div>
              <h3>Plan review &amp; takeoff</h3>
              <p>We price every opening from your plans or window schedule — sizes, egress, tempering, and Title 24 glass.</p>
            </div>
          </article>
          <article class="nc-process-item" role="listitem">
            <span class="nc-process-number" aria-hidden="true">02</span>
            <div>
              <h3>Product selection &amp; order</h3>
              <p>Compare brands and series that fit the architecture and budget, then lock lead times before framing closes in.</p>
            </div>
          </article>
          <article class="nc-process-item" role="listitem">
            <span class="nc-process-number" aria-hidden="true">03</span>
            <div>
              <h3>Delivery &amp; installation</h3>
              <p>Units arrive staged by floor and elevation. Our AAMA-certified crews flash, set, and seal to spec.</p>
            </div>
          </article>
          <article class="nc-process-item" role="listitem">
            <span class="nc-process-number" aria-hidden="true">04</span>
            <div>
              <h3>Punch list &amp; final walk</h3>
              <p>Every unit is checked for operation and finish before sign-off, with warranty documents handed over.</p>
            </div>
          </article>
        </div>
        <div class="nc-process-action">
          <a href="#wf-form-Main-Form" class="primary-button w-inline-block">
            <div class="text-block-22">Send Us Your Plans</div>
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section top-none" aria-labelledby="nc-products-heading">
  <div class="w-layout-blockcontainer container-default w-container">
    <div class="nc-products-heading-row">
      <div>
        <span class="nc-kicker">What we install</span>
        <h2 id="nc-products-heading" class="display-8 mid">Windows &amp; Doors for the Whole Build</h2>
      </div>
      <p>Every product below is available in new-construction configurations across the <span data-area-label>Bay Area</span>. We help match series and glass packages to your plans, elevation, and budget.</p>
    </div>
    <div class="nc-products-grid">
      @foreach($ncProducts as $product)
        <a href="{{ $product['href'] }}" class="nc-product-card">
          <div class="image-wrapper nc-product-image">
            <x-img
              :src="$product['image']"
              preset="card"
              loading="lazy"
              :alt="$product['name']"
              class="image cover-image"
            />
          </div>
          <div class="nc-product-content">
            <h3>{{ $product['name'] }}</h3>
            <p>{{ $product['description'] }}</p>
            <span class="nc-product-link">Explore options <span aria-hidden="true">→</span></span>
          </div>
        </a>
      @endforeach
    </div>
  </div>
</section>

<section class="section top-none">
  <div class="w-layout-blockcontainer container-default w-container">
    <div class="text-center---mbl">
      <h2 class="display-8 mid">Brands We Install</h2>
    </div>
    <div class="mg-top-default">
      @include('partials.brands')
    </div>
  </div>
</section>

@include('partials.for-professionals')

@include('partials.reviews')

@include('partials.guarantee')

@include('partials.certifications')

<div id="nc-contact">
  @include('partials.cta', [
    'ctaHref' => '#wf-form-Main-Form',
    'ctaTitleLine1' => 'One Estimate for',
    'ctaTitleLine2' => 'Every Opening in the House',
    'ctaText' => 'Send your plans or window schedule — we will return a complete takeoff with whole-house pricing and lead times.',
    'ctaButtonLabel' => 'Get a Free Project Estimate',
    'ctaImage' => '/webflow-assets/images/new-construction/cta.avif',
    'ctaImageAlt' => 'New construction home with modern glass doors and open indoor-outdoor living',
    'ctaImageClass' => 'cover-image',
  ])
</div>
@endsection

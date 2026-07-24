@extends('layouts.classic')

@section('wfPage', '6841ddf8ace3d9d9facb1672')
@section('pageWrapperClass', 'full-height-page')

@section('head')
<link rel="stylesheet" href="{{ asset('webflow-overrides/financing-page.css') }}" />
@endsection

@section('content')
      <section class="section_breadcrumbs section-121">
        <div class="w-layout-blockcontainer container-default breadcrumbs-container w-container">
          <div class="breadcrumbs-wrapper">
            <a href="/" class="breadcrumb-link">Home</a>
            <div class="breadcrumb-div">/</div>
            <div class="breadcrumb-text">Financing</div>
          </div>
        </div>
      </section>

      <section class="section hero-v8">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="inner-container _700px center page-intro-hero">
            <div class="text-center">
              <h1 class="display-9 mid">{{ $pageMetadata->h1 }}</h1>
              <div class="mg-top-small financing-intro">
                <p class="paragraph-50">
                  Flexible monthly payment plans for qualifying Bay Area window and door projects —
                  so you can upgrade now and pay over time. Terms depend on credit approval and project scope.
                </p>
              </div>
            </div>
          </div>

          <div class="mg-top-48px">
            <div class="w-layout-grid grid-3-columns pricing-grid">
              <div class="card plan-card">
                <div class="product-card---top-content">
                  <div class="center-content">
                    <div class="image-wrapper plan-icon">
                      <img alt="" loading="eager" src="/webflow-assets/images/684d5abd999584226df187df_star_rate_24dp_E3E3E3_FILL0_wght400_GRAD0_opsz24.svg" class="image cover-image" />
                    </div>
                    <div class="mg-top-small mg-top-16px---mbl">
                      <h2 class="display-5 mid">No FICO</h2>
                    </div>
                    <div class="div-block-37">
                      <p>Your credit rating does not impact your ability to qualify.</p>
                    </div>
                  </div>
                </div>
                <div class="product-card---bottom-content">
                  <div class="mg-top-default"><div class="divider"></div></div>
                  <div class="div-block-38">
                    <div class="w-layout-grid grid-1-column gap-row-16px">
                      <div class="feature-wrapper dark v1"><div class="check-icon feature-plan" aria-hidden="true">&#xE82F;</div><div class="display-2">No Credit Score Required</div></div>
                      <div class="feature-wrapper dark v1"><div class="check-icon feature-plan" aria-hidden="true">&#xE82F;</div><div class="display-2">Fast &amp; Simple Approval</div></div>
                      <div class="feature-wrapper dark v1"><div class="check-icon feature-plan" aria-hidden="true">&#xE82F;</div><div class="display-2">Improve Home Value Now</div></div>
                      <div class="feature-wrapper dark v1"><div class="check-icon feature-plan" aria-hidden="true">&#xE82F;</div><div class="display-2">Second Chance Financing</div></div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="card plan-card">
                <div class="product-card---top-content">
                  <div class="center-content">
                    <div class="image-wrapper plan-icon">
                      <img alt="" loading="eager" src="/webflow-assets/images/684d5b02ab39a043513ad935_trending_down_24dp_E3E3E3_FILL0_wght400_GRAD0_opsz24.svg" class="image cover-image" />
                    </div>
                    <div class="mg-top-small mg-top-16px---mbl">
                      <h2 class="display-5 mid">Lower Fixed Rates</h2>
                    </div>
                    <div class="mg-top-extra-small">
                      <p>Payment remains the same for the life of your financing.</p>
                    </div>
                  </div>
                </div>
                <div class="product-card---bottom-content">
                  <div class="mg-top-default"><div class="divider"></div></div>
                  <div class="div-block-39">
                    <div class="w-layout-grid grid-1-column gap-row-16px">
                      <div class="feature-wrapper dark v1"><div class="check-icon feature-plan" aria-hidden="true">&#xE82F;</div><div class="display-2">Budget-Friendly Payments</div></div>
                      <div class="feature-wrapper dark v1"><div class="check-icon feature-plan" aria-hidden="true">&#xE82F;</div><div class="display-2">Long-Term Savings</div></div>
                      <div class="feature-wrapper dark v1"><div class="check-icon feature-plan" aria-hidden="true">&#xE82F;</div><div class="display-2">Secure &amp; Stable</div></div>
                      <div class="feature-wrapper dark v1"><div class="check-icon feature-plan" aria-hidden="true">&#xE82F;</div><div class="display-2">Immediate Upgrades</div></div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="card plan-card">
                <div class="product-card---top-content">
                  <div class="center-content">
                    <div class="image-wrapper plan-icon">
                      <img alt="" loading="eager" src="/webflow-assets/images/684d5b0fe6b11bd987eb461c_all_inclusive_24dp_E3E3E3_FILL0_wght400_GRAD0_opsz24.svg" class="image cover-image" />
                    </div>
                    <div class="mg-top-small mg-top-16px---mbl">
                      <h2 class="display-5 mid">Longer Terms</h2>
                    </div>
                    <div class="mg-top-extra-small">
                      <p>Flexible repayment terms — up to 30 years for some projects.</p>
                    </div>
                  </div>
                </div>
                <div class="product-card---bottom-content">
                  <div class="mg-top-default"><div class="divider"></div></div>
                  <div class="div-block-40">
                    <div class="w-layout-grid grid-1-column gap-row-16px">
                      <div class="feature-wrapper dark v1"><div class="check-icon feature-plan" aria-hidden="true">&#xE82F;</div><div class="display-2">Custom Repayment Plans</div></div>
                      <div class="feature-wrapper dark v1"><div class="check-icon feature-plan" aria-hidden="true">&#xE82F;</div><div class="display-2">More Buying Power</div></div>
                      <div class="feature-wrapper dark v1"><div class="check-icon feature-plan" aria-hidden="true">&#xE82F;</div><div class="display-2">Up to 30-Year Terms</div></div>
                      <div class="feature-wrapper dark v1"><div class="check-icon feature-plan" aria-hidden="true">&#xE82F;</div><div class="display-2">Upgrade Without Stress</div></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="section top-none financing-process">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="w-layout-grid grid-2-columns values-wrapper-grid">
            <div class="sticky-top static---tablet">
              <div class="inner-container _500px _100-tablet">
                <div class="inner-container _600px---tablet">
                  <div class="mg-top-default"><h2 class="heading-10">How financing works</h2></div>
                  <div class="mg-top-small">
                    <p>
                      We walk you through approval with our financing partners, then install your windows and doors.
                      Exact rates and terms are confirmed after your free estimate.
                    </p>
                  </div>
                </div>
              </div>
            </div>
            <div class="inner-container _592px _100-tablet">
              <div class="w-layout-grid grid-2-columns values-grid">
                <div class="value-wrapper">
                  <div class="image-wrapper">
                    <img src="/webflow-assets/images/684d86f32d344f16ce6ec364_flag_24dp_E3E3E3_FILL0_wght400_GRAD0_opsz24.svg" loading="eager" alt="" class="image" />
                  </div>
                  <div class="mg-top-small"><h3 class="display-5 mid">1. Apply</h3></div>
                  <div class="mg-top-extra-small">
                    <p class="paragraph-5">Request a free estimate. We’ll help you start the financing application and explain available options for your project.</p>
                  </div>
                </div>
                <div class="value-wrapper">
                  <div class="image-wrapper">
                    <img src="/webflow-assets/images/684d86ff1fff20336f975d74_shopping_bag_24dp_E3E3E3_FILL0_wght400_GRAD0_opsz24.svg" loading="eager" alt="" class="image" />
                  </div>
                  <div class="mg-top-small"><h3 class="display-5 mid">2. Sign</h3></div>
                  <div class="mg-top-extra-small">
                    <p class="paragraph-6">Review and sign financing documents electronically — no trip to the bank required.</p>
                  </div>
                </div>
                <div class="value-wrapper">
                  <div class="image-wrapper">
                    <img src="/webflow-assets/images/684d870c533c4f729eb8094c_settings_24dp_E3E3E3_FILL0_wght400_GRAD0_opsz24.svg" loading="eager" alt="" class="image" />
                  </div>
                  <div class="mg-top-small"><h3 class="display-5 mid">3. Install</h3></div>
                  <div class="mg-top-extra-small">
                    <p class="paragraph-7">Once approved, we schedule installation of your energy-efficient windows and doors.</p>
                  </div>
                </div>
                <div class="value-wrapper">
                  <div class="image-wrapper">
                    <img src="/webflow-assets/images/684d8718e99d2a34dfef7e4d_home_24dp_E3E3E3_FILL0_wght400_GRAD0_opsz24.svg" loading="eager" alt="" class="image" />
                  </div>
                  <div class="mg-top-small"><h3 class="display-5 mid">4. Enjoy</h3></div>
                  <div class="mg-top-extra-small">
                    <p class="paragraph-7">Make predictable monthly payments while your home is more comfortable and energy-efficient.</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="section top-none">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="card template-pages---text-card financing-note">
            <h2 class="mg-bottom-small">Important details</h2>
            <p class="mg-bottom-default">
              Financing is offered through third-party lenders and is subject to credit approval.
              Monthly payments, APR, and loan length vary by lender, credit profile, and project amount.
              Promotional offers (including limited-time 0% APR periods, when available) are confirmed in writing
              during your free Bay Area consultation — not on this page alone.
            </p>
            <p class="mg-bottom-default">
              Prefer to talk it through? Call
              <a href="tel:{{ site_phone_tel() }}">{{ site_phone_display() }}</a>
              or request an estimate below.
            </p>
          </div>
        </div>
      </section>

      @include('partials.cta', ['ctaHref' => '#contact'])

      @include('partials.contacts-webflow-section')
@endsection

@push('scripts')
@include('partials.utm-tracking')
@endpush

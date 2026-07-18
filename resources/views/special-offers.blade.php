@php
  $cdn = 'https://cdn.prod.website-files.com/6841ddf8ace3d9d9facb14fd';
  $calendarIcon = '/webflow-assets/images/6841ddf8ace3d9d9facb1894_calendar-icon-property-x-webflow-template.svg';
@endphp
@extends('layouts.classic')

@section('wfPage', '687a4292617b9b4ed5cfe680')

@section('content')
      <section class="section_breadcrumbs section-121">
        <div class="w-layout-blockcontainer container-default breadcrumbs-container w-container">
          <div class="breadcrumbs-wrapper">
            <a href="/" class="breadcrumb-link">Home</a>
            <div class="breadcrumb-div">/</div>
            <div class="breadcrumb-text">Special offers</div>
          </div>
        </div>
      </section>

      <section class="section hero-v8">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="inner-container _600px center page-intro-hero">
            <div class="text-center">
              <div class="inner-container _500px---mbl center">
                <h1 class="display-9 mid">Limited-Time Window &amp; Doors Replacement Offers<br /></h1>
              </div>
            </div>
          </div>
        </div>
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="mg-top-large mg-top-40px---mbl">
            <div class="w-dyn-list">
              <div role="list" class="grid-1-column featured-blog-grid-v3 w-dyn-items">
                @foreach($coupons as $coupon)
                <div role="listitem" class="featured-blog-card-v3 w-dyn-item">
                  @if($coupon['image'] !== '')
                  <a href="#email-form-2" class="image-wrapper featured-blog-v3 w-inline-block">
                    <img src="{{ $coupon['image'] }}" loading="eager" alt="" class="image cover-image" />
                  </a>
                  @endif
                  <a href="#email-form-2" class="card featured-card-blog-v3 w-inline-block">
                    <div>
                      <div class="card-post-date">
                        <img src="{{ $calendarIcon }}" loading="eager" alt="Calendary Icon - Property X Webflow Template" />
                        <div class="text-neutral-light"><div>{{ $coupon['expires_label'] }}</div></div>
                      </div>
                    </div>
                    <div class="inner-container _450px---mbl">
                      <div class="mg-top-default"><h2 class="display-6">{{ $coupon['name'] }}</h2></div>
                      <div class="mg-top-small">
                        <p class="paragraph-50">{{ $coupon['description'] }}</p>
                      </div>
                    </div>
                  </a>
                </div>
                @endforeach
              </div>
            </div>
          </div>
        </div>
      </section>

      @include('partials.special-offers-contact-section')

      <section class="section-card-wrapper cta-v3"></section>
@endsection

@extends('layouts.classic')

@section('wfPage', '687a8de5e8e76e587d2190ad')

@section('bodyClass', 'body-18 height-auto testimonials-page')

@section('content')
      <section class="section_breadcrumbs section-121">
        <div class="w-layout-blockcontainer container-default breadcrumbs-container w-container">
          <div class="breadcrumbs-wrapper">
            <a href="/" class="breadcrumb-link">Home</a>
            <div class="breadcrumb-div">/</div>
            <div class="breadcrumb-text">Testimonials</div>
          </div>
        </div>
      </section>

      <section class="section pd-top-80px top-none">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="mg-top-extra-large">
            <div class="title-left---content-right">
              <h1 class="display-9 mid">Look at What People Say About Us</h1>
            </div>
          </div>
        </div>
      </section>

      @include('partials.yelp-reviews', [
        'yelpShowHeading' => false,
        'yelpInitialCount' => 12,
      ])
@endsection

@push('scripts')
@include('partials.utm-tracking')
@endpush

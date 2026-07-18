@extends('layouts.classic')

@section('wfPage', '6841ddf8ace3d9d9facb15cd')
@section('bodyClass', 'body-18 height-auto')

@section('content')
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
              <h1 class="display-10 mid text-light">High-Quality Doors <br />for Your Home</h1>
            </div>
          </div>
          <div class="mg-top-small">
            <div class="inner-container _562px center">
              <div class="text-neutral-light">
                <p class="paragraph-26">Upgrade your home with secure, stylish, and energy-efficient doors. Explore a full range of entry and patio doors made for American homes.</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="section">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="collection-list-wrapper-2 w-dyn-list">
            <div role="list" class="grid-2-columns properties-grid---v1 w-dyn-items">
              @foreach($doors as $door)
                @include('partials.doors-index-card', ['door' => $door])
              @endforeach
            </div>
          </div>
        </div>
      </section>
@endsection

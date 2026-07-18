@extends('layouts.classic')

@section('wfPage', 'gallery')
@section('bodyClass', 'body-18 height-auto gallery-page')

@section('head')
    @php
      $galleryCssPath = public_path('webflow-overrides/gallery.css');
      $galleryCssVersion = file_exists($galleryCssPath) ? (string) filemtime($galleryCssPath) : '1';
    @endphp
    <link href="/webflow-overrides/gallery.css?v={{ $galleryCssVersion }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
      <section class="section_breadcrumbs section-121">
        <div class="w-layout-blockcontainer container-default breadcrumbs-container w-container">
          <div class="breadcrumbs-wrapper">
            <a href="/" class="breadcrumb-link">Home</a>
            <div class="breadcrumb-div">/</div>
            <div class="breadcrumb-text">Photo Gallery</div>
          </div>
        </div>
      </section>

      <section class="section pd-top-80px top-none">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="mg-top-extra-large">
            <div class="title-left---content-right">
              <h1 class="display-9 mid">Photo Gallery</h1>
            </div>
            <div class="mg-top-small">
              <p class="paragraph-20">
                Browse through our photo gallery showcasing Deluxe Windows' windows and doors projects to spark your imagination.
              </p>
              <p class="gallery-intro-note">
                Some projects were completed during previous ownership. All photos taken by Felix, highlighting his professional experience.
              </p>
            </div>
          </div>
        </div>
      </section>

      <section class="section dw-gallery-section">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="dw-gallery-grid">
            @foreach($images as $image)
              <a href="{{ $image }}" target="_blank" class="dw-gallery-item" rel="noopener noreferrer">
                <img src="{{ $image }}" alt="Deluxe Windows project" loading="lazy" />
              </a>
            @endforeach
          </div>
        </div>
      </section>

      @include('partials.cta')
@endsection

@push('scripts')
@include('partials.utm-tracking')
@endpush

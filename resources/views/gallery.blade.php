@extends('layouts.classic')

@section('wfPage', 'gallery')
@section('bodyClass', 'body-18 height-auto gallery-page')
@section('title', 'Photo Gallery | Deluxe Windows – Bay Area')
@section('metaDescription', 'Browse our photo gallery of completed window and door replacement projects across the Bay Area by Deluxe Windows.')

@section('head')
    <style>
      .dw-gallery-grid {
        columns: 4;
        column-gap: 8px;
      }
      .dw-gallery-item {
        break-inside: avoid;
        display: block;
        margin-bottom: 8px;
        border-radius: 10px;
        overflow: hidden;
        background: #e8edf2;
        position: relative;
      }
      .dw-gallery-item img {
        width: 100%;
        display: block;
        transition: transform 0.35s ease;
      }
      .dw-gallery-item::after {
        content: '';
        position: absolute;
        inset: 0;
        background: rgba(15, 42, 72, 0);
        transition: background 0.25s ease;
        pointer-events: none;
      }
      .dw-gallery-item:hover img {
        transform: scale(1.04);
      }
      .dw-gallery-item:hover::after {
        background: rgba(15, 42, 72, 0.1);
      }
      .gallery-intro-note {
        font-size: 0.88rem;
        color: #7a8fa6;
        margin-top: 10px;
        font-style: italic;
      }
      .dw-gallery-section {
        padding-top: 32px;
      }
      @media (max-width: 991px) {
        .dw-gallery-grid { columns: 3; }
      }
      @media (max-width: 640px) {
        .dw-gallery-grid { columns: 2; column-gap: 6px; }
        .dw-gallery-item { margin-bottom: 6px; border-radius: 7px; }
      }
    </style>
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

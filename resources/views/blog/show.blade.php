@extends('layouts.classic')

@section('wfPage', '687b79c6ee572b31129b17c3')
@section('wfCollection', '687b79c5ee572b31129b17bf')
@section('wfItemSlug', $slug)
@section('bodyClass', 'body-18 height-auto')

@section('content')
      <section class="section_breadcrumbs section-121">
        <div class="w-layout-blockcontainer container-default breadcrumbs-container w-container">
          <div class="breadcrumbs-wrapper">
            <a href="/" class="breadcrumb-link">Home</a>
            <div class="breadcrumb-div">/</div>
            <a href="/blog" class="breadcrumb-link hidden-link">Blog</a>
            <div class="breadcrumb-div hidden-txt">/</div>
            <div class="breadcrumb-text">{{ $title }}</div>
          </div>
        </div>
      </section>

      <section class="section hero-v4">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="blog-post-single---top-content">
            <div class="inner-container _770px">
              <div class="inner-container _550px---tablet">
                <div class="mg-top-small">
                  <h1 class="display-9 mid">{{ $title }}</h1>
                </div>
              </div>
            </div>
          </div>

          @if($heroImage)
          <div class="mg-top-extra-large">
            <div class="image-wrapper border-radius-image-default">
              <x-img :src="$heroImage" preset="content" :alt="$title" loading="eager" class="image post---featured-image" />
            </div>
          </div>
          @endif

          @if($bodyHtml)
          <div class="mg-top-section-large">
            <div class="inner-container _690px center">
              <div class="rich-text-v1 mg-bottom--16px w-richtext">
                {!! $bodyHtml !!}
              </div>
            </div>
          </div>
          @endif
        </div>
      </section>

      @if($relatedPosts->count() > 0)
      <section class="section pd-120px">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="title-left---content-right">
            <div class="width-100-mobile-portrait">
              <h2 class="display-8 mid">Read More Articles</h2>
            </div>
          </div>
          <div class="mg-top-large">
            <div class="w-dyn-list">
              <div role="list" class="collection-list-8 w-dyn-items">
                @foreach($relatedPosts as $post)
                  @include('partials.blog-index-card', [
                    'post' => $post,
                    'loading' => $loop->first ? 'eager' : 'lazy',
                  ])
                @endforeach
              </div>
            </div>
          </div>
        </div>
      </section>
      @endif

      @include('partials.blog-page-bottom', [
        'wfPageId' => '687b79c6ee572b31129b17c3',
        'ctaHeadingClass' => 'heading-43',
        'contactHeadingClass' => 'heading-34',
      ])
@endsection

@push('scripts')
@include('partials.utm-tracking')
@endpush

@extends('layouts.classic')

@section('wfPage', '688097fa174129b5ec241dd4')
@section('bodyClass', 'body-18 height-auto blog-page')

@section('content')
      <section class="section_breadcrumbs section-121">
        <div class="w-layout-blockcontainer container-default breadcrumbs-container w-container">
          <div class="breadcrumbs-wrapper">
            <a href="/" class="breadcrumb-link">Home</a>
            <div class="breadcrumb-div">/</div>
            <div class="breadcrumb-text">Blog</div>
          </div>
        </div>
      </section>

      <section class="section pd-120px">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="inner-container _600px center page-intro-hero">
            <div class="text-center">
              <div class="inner-container _500px---mbl center">
                <h1 class="display-9 mid">Knowledge Articles<br /></h1>
              </div>
            </div>
          </div>
        </div>
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="mg-top-large">
            <div class="collection-list-wrapper-8 w-dyn-list">
              <div role="list" class="collection-list-5 w-dyn-items">
                @foreach($posts as $post)
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

      @include('partials.blog-page-bottom')
@endsection

@push('scripts')
@include('partials.utm-tracking')
@endpush

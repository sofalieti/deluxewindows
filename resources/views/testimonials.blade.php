@extends('layouts.classic')

@section('wfPage', '687a8de5e8e76e587d2190ad')
@section('title', $seoTitle)
@section('metaDescription', $seoDescription)
@section('ogImage', $ogImage)

@section('head')
    <link href="https://static.elfsight.com/" rel="preconnect" crossorigin="anonymous" />
@endsection

@section('content')
      <section class="section hero-v4">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="inner-container _600px center page-intro-hero">
            <div class="text-center">
              <div class="inner-container _500px---mbl center">
                <h1 class="display-9 mid">Look at <br/>What People Say <span class="text-no-wrap">About Us</span><br/></h1>
              </div>
            </div>
          </div>
        </div>
      </section>

      <div class="w-embed w-script">
        <!-- Elfsight Yelp Reviews | Untitled Yelp Reviews -->
        <script src="https://static.elfsight.com/platform/platform.js" async></script>
        <div class="elfsight-app-9b5ea9e5-b8e2-46ee-a99c-1e6552b85f66" data-elfsight-app-lazy></div>
      </div>
@endsection

@push('scripts')
@include('partials.utm-tracking')
@endpush

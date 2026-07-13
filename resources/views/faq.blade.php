@extends('layouts.classic')

@section('wfPage', '684d99edd99a23e6749ec7b8')
@section('title', $seoTitle)
@section('metaDescription', $seoDescription)
@section('ogImage', $ogImage)

@section('content')
      <section class="section-card-wrapper top page-intro-hero">
        <div class="section-card hero-card---120px-page">
          <div class="w-layout-blockcontainer container-default w-container">
            <div class="inner-container _850px center">
              <div class="inner-container _600px---tablet center">
                <div class="center-content">
                  <div class="w-layout-vflex inner-container _500px---mbl center">
                    <div class="mg-top-small">
                      <h1 class="display-10 mid text-light">Frequently Asked Questions</h1>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="section pd-120px">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="grid-2-columns template-page-sidebar">
            <div class="card template-pages---sticky-card">
              <ul role="list" class="template-pages---sidebar-navigation w-list-unstyled">
                @foreach($navItems as $item)
                <li class="template-pages---nav-item-wrapper">
                  <a href="#{{ $item['id'] }}" class="template-pages---nav-item-link">{{ $item['label'] }}</a>
                </li>
                @endforeach
              </ul>
            </div>

            <div class="card template-pages---text-card">
              @foreach($sections as $sectionIndex => $section)
              @if($sectionIndex > 0)
              <div class="divider mg-large"></div>
              @endif
              <div id="{{ $section['id'] }}">
                <h2 class="mg-bottom-small">{{ $section['title'] }}</h2>
                @foreach($section['blocks'] as $block)
                @if($block['tag'] === 'h4')
                <h4 class="{{ $block['class'] ?? '' }}">{!! $block['html'] !!}</h4>
                @else
                <{{ $block['tag'] }} class="{{ $block['class'] ?? '' }}">{!! $block['html'] !!}</{{ $block['tag'] }}>
                @endif
                @endforeach
              </div>
              @endforeach
            </div>
          </div>
        </div>
      </section>

      <div class="divider mg-large"></div>
@endsection

@push('scripts')
@include('partials.utm-tracking')
@endpush

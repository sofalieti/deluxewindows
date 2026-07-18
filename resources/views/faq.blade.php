@extends('layouts.classic')

@section('wfPage', '684d99edd99a23e6749ec7b8')
@section('metadataFaqRendered', '1')

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
                @foreach($pageMetadata->faq as $index => $item)
                <li class="template-pages---nav-item-wrapper">
                  <a href="#faq-{{ $index + 1 }}" class="template-pages---nav-item-link">{{ $item['question'] }}</a>
                </li>
                @endforeach
              </ul>
            </div>

            <div class="card template-pages---text-card">
              @foreach($pageMetadata->faq as $sectionIndex => $item)
              @if($sectionIndex > 0)
              <div class="divider mg-large"></div>
              @endif
              <div id="faq-{{ $sectionIndex + 1 }}">
                <h2 class="mg-bottom-small">{{ $item['question'] }}</h2>
                <p class="mg-bottom-default">{{ $item['answer'] }}</p>
              </div>
              @endforeach
            </div>
          </div>
        </div>
      </section>

      <section class="new-section">
        <div class="w-layout-blockcontainer container-default w-container">
          <div class="text-block-44">* Price applies to minimum window installation size of 24&quot;x24&quot;.</div>
        </div>
      </section>

      <div class="divider mg-large"></div>
@endsection

@push('scripts')
@include('partials.utm-tracking')
@endpush

@extends('layouts.classic')

@section('wfPage', '69ce789764cd8d5d1bcf1ae2')
@section('wfCollection', '69ce789764cd8d5d1bcf1aa4')
@section('wfItemSlug', $slug)
@section('bodyClass', 'body-20')

@section('content')
    @include('partials.county-hub-hero', [
      'countyName' => $countyName,
      'heroImage' => $heroImage,
    ])

    @include('partials.trust-badges')

    <section class="sectionmain">
      <div class="w-layout-blockcontainer container-default w-container">
        <div class="w-layout-grid grid-545 _324234">
          <div class="div-block-63 title-left---content-right---title-grow-v1">
            <div class="code-embed-9 w-embed">
              <h2 class="display-8 mid types">Window Replacement Services in {{ $countyName }}</h2>
            </div>
            @if($countyIntro)
            <div class="rich-text-block-11 w-richtext">
              {!! $countyIntro !!}
            </div>
            @endif
            @if($cities->count() > 0)
            <div class="collection-list-wrapper-22 w-dyn-list">
              <div role="list" class="collection-list-15 w-dyn-items">
                @foreach($cities as $city)
                <div role="listitem" class="collection-item-14 w-dyn-item">
                  <a href="/window-replacement/{{ $city['slug'] }}" class="city-block w-inline-block">
                    <div>{{ $city['name'] }}</div>
                  </a>
                </div>
                @endforeach
              </div>
            </div>
            @endif
          </div>
        </div>
      </div>
    </section>

    @include('partials.service-area-brands', [
      'cityName' => $countyName,
      'featuredBrands' => $featuredBrands,
    ])

    @include('partials.county-hub-pricing', ['countyName' => $countyName])
    @include('partials.county-hub-process', ['countyName' => $countyName])
    @include('partials.county-hub-bottom-cta', ['countyName' => $countyName])

@endsection

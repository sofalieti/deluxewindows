@extends('layouts.classic')

@section('wfPage', '69ce7898d019bc268b4bb9e4')
@section('wfCollection', '69ce7898d019bc268b4bb9ca')
@section('wfItemSlug', $slug)
@section('bodyClass', 'body-19')

@section('content')
    @include('partials.service-area-hero', [
      'cityName' => $cityName,
      'cityLabel' => $cityLabel,
      'countyName' => $countyName,
      'heroImage' => $heroImage,
      'localPhone' => $localPhone,
    ])

    @include('partials.trust-badges')

    @include('partials.service-area-main', [
      'cityName' => $cityName,
      'cityLabel' => $cityLabel,
      'countyName' => $countyName,
      'countyHubSlug' => $countyHubSlug,
      'paragraph1' => $paragraph1,
      'paragraph2' => $paragraph2,
    ])

    @include('partials.service-area-window-types', [
      'cityName' => $cityName,
      'windowTypes' => $windowTypes,
    ])

    @include('partials.service-area-brands', [
      'cityName' => $cityName,
      'featuredBrands' => $featuredBrands,
    ])

    @include('partials.county-hub-pricing', ['pricingTitle' => $cityName])

    @include('partials.service-area-process')

    @include('partials.service-area-why', [
      'cityName' => $cityName,
      'countyName' => $countyName,
    ])

    @include('partials.guarantee')

    @include('partials.reviews')

    @include('partials.county-hub-bottom-cta', [
      'ctaLocationLabel' => $cityName,
      'localPhone' => $localPhone,
    ])

@endsection

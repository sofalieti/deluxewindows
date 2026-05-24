@extends('webflow.layouts.app')

@section('title', 'Brands')

@section('content')
<div class="container py-4">
    <h1 class="mb-2">Brands</h1>
    <p class="text-secondary mb-4">Webflow slug: <code>brand</code> | Path: <code>/brand</code></p>

    <section class="mb-3">{!! '<h1 class="heading-32">Trusted Brands We Work With</h1>' !!}</section>
    <section class="mb-3">{!! '<div class="text-size-16 text-color-dark-grey is-mob-centre">Explore our curated selection of top-tier window and door manufacturers to<br data-w-id="87a5e28b-903d-26b7-a23d-60a567a6e61f">find the perfect fit for your project.</div>' !!}</section>
    <section class="mb-3">{!! '<div class="filter-btn-txt">Materials</div>' !!}</section>
    <section class="mb-3">{!! '<label class="checkbox-label">Aluminum</label>' !!}</section>
    <section class="mb-3">{!! '<label class="checkbox-label">Aluminum Clad</label>' !!}</section>
    <section class="mb-3">{!! '<label class="checkbox-label">Fiberglass</label>' !!}</section>
    <section class="mb-3">{!! '<label class="checkbox-label">Steel</label>' !!}</section>
    <section class="mb-3">{!! '<label class="checkbox-label">Vinyl</label>' !!}</section>
    <section class="mb-3">{!! '<label class="checkbox-label">Wood</label>' !!}</section>
    <section class="mb-3">{!! '<label class="checkbox-label">Wood Clad</label>' !!}</section>
    <section class="mb-3">{!! '<div class="filter-btn-txt">Price Range</div>' !!}</section>
    <section class="mb-3">{!! '<label class="checkbox-label">$</label>' !!}</section>
    <section class="mb-3">{!! '<label class="checkbox-label">$$</label>' !!}</section>
    <section class="mb-3">{!! '<label class="checkbox-label">$$$</label>' !!}</section>
    <section class="mb-3">{!! '<label class="checkbox-label">$$$$</label>' !!}</section>
    <section class="mb-3">{!! '<label class="checkbox-label">$$$$$</label>' !!}</section>
    <section class="mb-3">{!! '<a class="text-size-14 is-link" href="#">Clear all</a>' !!}</section>
    <section class="mb-3">{!! '<div>Thank you! Your submission has been received!</div>' !!}</section>
    <section class="mb-3">{!! '<div>Oops! Something went wrong while submitting the form.</div>' !!}</section>
    <section class="mb-3">{!! '<a class="material-tag-btn" href="#"></a>' !!}</section>
    <section class="mb-3">{!! '<div>No items found.</div>' !!}</section>
    <section class="mb-3">{!! '<div class="brand_item-title">KEY FEATURES</div>' !!}</section>
    <section class="mb-3">{!! '<div class="brand_item-title">Energy Efficiency</div>' !!}</section>
    <section class="mb-3">{!! '<div class="brand_item-title">Sound Insulation</div>' !!}</section>
    <section class="mb-3">{!! '<div class="brand_item-title">Warranty</div>' !!}</section>
    <section class="mb-3">{!! '<div class="text-size-14 tezt-color-orange">View collections</div>' !!}</section>
    <section class="mb-3">{!! '<div class="icon-font-rounded arrow no-rotation"></div>' !!}</section>
    <section class="mb-3">{!! '<div>$</div>' !!}</section>
    <section class="mb-3">{!! '<div>$$</div>' !!}</section>
    <section class="mb-3">{!! '<div>$$$</div>' !!}</section>
    <section class="mb-3">{!! '<div>$$$$</div>' !!}</section>
    <section class="mb-3">{!! '<div>$$$$$</div>' !!}</section>
    <section class="mb-3">{!! '<div>No brands found.</div>' !!}</section>
    <section class="mb-3">{!! '<div class="text-size-16 text-align-centre">Not sure which brand is right for you?</div>' !!}</section>
    <section class="mb-3">{!! '<div class="text-block">Get a free consultation</div>' !!}</section>

    @if(!empty($items))
    <section class="mt-4">
        <h2 class="h5 mb-3">Collection items</h2>
        <div class="row g-3">
            @foreach($items as $item)
            <div class="col-12 col-md-6 col-lg-4">
                <article class="card h-100">
                    <div class="card-body">
                        <h3 class="h6">{{ data_get($item, 'field_data.name', 'Untitled') }}</h3>
                        <pre class="small mb-0">{{ json_encode($item['field_data'] ?? [], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                </article>
            </div>
            @endforeach
        </div>
    </section>
    @endif
</div>
@endsection

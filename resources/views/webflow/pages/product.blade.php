@extends('webflow.layouts.app')

@section('title', '👕 Products Template')

@section('content')
<div class="container py-4">
    <h1 class="mb-2">👕 Products Template</h1>
    <p class="text-secondary mb-4">Webflow slug: <code>detail_product</code> | Path: <code>/product</code></p>

    <section class="mb-3">{!! '<h1 class="display-9 mid text-light"></h1>' !!}</section>
    <section class="mb-3">{!! '<p></p>' !!}</section>
    <section class="mb-3">{!! '<h2 class="display-5 mid text-light">What’s included?</h2>' !!}</section>
    <section class="mb-3">{!! '<div class="check-icon light"></div>' !!}</section>
    <section class="mb-3">{!! '<div class="display-2"></div>' !!}</section>
    <section class="mb-3">{!! '<div class="check-icon light"></div>' !!}</section>
    <section class="mb-3">{!! '<div class="display-2"></div>' !!}</section>
    <section class="mb-3">{!! '<div class="check-icon light"></div>' !!}</section>
    <section class="mb-3">{!! '<div class="display-2"></div>' !!}</section>
    <section class="mb-3">{!! '<div class="check-icon light"></div>' !!}</section>
    <section class="mb-3">{!! '<div class="display-2"></div>' !!}</section>
    <section class="mb-3">{!! '<h2 class="display-5 mid text-light">Pay your <span class="text-no-wrap" data-w-id="99d434b2-f2a0-b1ac-ed98-5dfe41481a50">property credit</span></h2>' !!}</section>
    <section class="mb-3">{!! '<p>Lorem ipsum dolor sit amet consectetur. Sit ut gravida aenean potenti. Metus <span class="text-no-wrap" data-w-id="450361cb-11b5-b4fa-00f1-4792db813e92">in eu vel.</span></p>' !!}</section>
    <section class="mb-3">{!! '<div class="display-8 mid text-light"></div>' !!}</section>
    <section class="mb-3">{!! '<div class="display-4 mid">USD</div>' !!}</section>
    <section class="mb-3">{!! '<div></div>' !!}</section>
    <section class="mb-3">{!! '<label for="quantity-117072ec1f2fd06cc1c337018c0acc65" class="hidden">Quantity</label>' !!}</section>
    <section class="mb-3">{!! '<div class="icon-font-rounded"></div>' !!}</section>
    <section class="mb-3">{!! '<div class="link buy-now-link"></div>' !!}</section>
    <section class="mb-3">{!! '<div class="icon-font-rounded"></div>' !!}</section>
    <section class="mb-3">{!! '<div class="text-neutral-light text-left">This product is out of stock.</div>' !!}</section>
    <section class="mb-3">{!! '<div class="rich-text-v2 mg-bottom--16px"></div>' !!}</section>

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

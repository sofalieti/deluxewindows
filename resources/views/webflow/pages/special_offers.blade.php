@extends('webflow.layouts.app')

@section('title', 'Special Offers')

@section('content')
<div class="container py-4">
    <h1 class="mb-2">Special Offers</h1>
    <p class="text-secondary mb-4">Webflow slug: <code>special-offers</code> | Path: <code>/special-offers</code></p>

    <section class="mb-3">{!! '<h1 class="display-9 mid">Limited-Time Window &amp; Doors Replacement Offers<br data-w-id="4d207b89-80d1-36cc-0239-a22bdf74d8fe"></h1>' !!}</section>
    <section class="mb-3">{!! '<h1>Schedule Your Coupon</h1>' !!}</section>
    <section class="mb-3">{!! '<p class="paragraph-8">We’re here to help with all your door and window needs.</p>' !!}</section>
    <section class="mb-3">{!! '<div class="text-block-3">Phone number</div>' !!}</section>
    <section class="mb-3">{!! '<div>(650) 461-4446</div>' !!}</section>
    <section class="mb-3">{!! '<h2 class="display-33">Get Deluxe Windows for Less. 40%&nbsp;OFF* Windows</h2>' !!}</section>
    <section class="mb-3">{!! '<label for="email-banner" class="body-14"><em class="italic-text" data-w-id="5b9eb27e-57ba-7166-a0b8-a976f39b6cbd">*Full Home Siding. Offer Expires </em><span data-w-id="89ef6e02-4ae0-58ca-2aea-6843084e0f74"><em class="italic-text" data-w-id="89ef6e02-4ae0-58ca-2aea-6843084e0f75">7/30/25</em></span></label>' !!}</section>
    <section class="mb-3">{!! '<label for="email-banner" class="body-14">Request a FREE No-Obligation Quote &amp; Expert Advice!</label>' !!}</section>
    <section class="mb-3">{!! '<label for="Name-2">Full name*</label>' !!}</section>
    <section class="mb-3">{!! '<div class="filled-icons-font"></div>' !!}</section>
    <section class="mb-3">{!! '<label for="Email-2">Email*</label>' !!}</section>
    <section class="mb-3">{!! '<div class="filled-icons-font"></div>' !!}</section>
    <section class="mb-3">{!! '<label for="Phone-2">Phone*</label>' !!}</section>
    <section class="mb-3">{!! '<div class="filled-icons-font"></div>' !!}</section>
    <section class="mb-3">{!! '<label for="Company">City</label>' !!}</section>
    <section class="mb-3">{!! '<label for="Message-2">Listing short description</label>' !!}</section>
    <section class="mb-3">{!! '<div>Thank you! Your submission has been received!</div>' !!}</section>
    <section class="mb-3">{!! '<div>Oops! Something went wrong while submitting the form.</div>' !!}</section>

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

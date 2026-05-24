@extends('webflow.layouts.app')

@section('title', 'Collections Template')

@section('content')
<div class="container py-4">
    <h1 class="mb-2">Collections Template</h1>
    <p class="text-secondary mb-4">Webflow slug: <code>detail_collection</code> | Path: <code>/collection</code></p>

    <section class="mb-3">{!! '<div class="display-4 mid">Get Deluxe Windows for Less. 40% OFF* Windows</div>' !!}</section>
    <section class="mb-3">{!! '<p class="text-titles"><em data-w-id="f6034b9c-d79b-3948-13c1-259278c759f7">Request a FREE No-Obligation Quote &amp; Expert Advice!</em><br data-w-id="f6034b9c-d79b-3948-13c1-259278c759f9"></p>' !!}</section>
    <section class="mb-3">{!! '<div class="filled-icons-font"></div>' !!}</section>
    <section class="mb-3">{!! '<div class="filled-icons-font"></div>' !!}</section>
    <section class="mb-3">{!! '<div class="filled-icons-font"></div>' !!}</section>
    <section class="mb-3">{!! '<div class="icon-font-rounded success-message-icon"></div>' !!}</section>
    <section class="mb-3">{!! '<div class="display-1">Thank you! We’ll get back to you soon<br data-w-id="f6034b9c-d79b-3948-13c1-259278c75a17"></div>' !!}</section>
    <section class="mb-3">{!! '<div>Oops! Something went wrong.</div>' !!}</section>
    <section class="mb-3">{!! '<div>No items found.</div>' !!}</section>
    <section class="mb-3">{!! '<h2 class="heading-8">4 Easy Steps</h2>' !!}</section>
    <section class="mb-3">{!! '<p>Our step-by-step process is designed to make replacing your windows and doors easy, stress-free, and fully tailored to your needs — from the first estimate to the final inspection.</p>' !!}</section>
    <section class="mb-3">{!! '<h3 class="display-5 mid">Start</h3>' !!}</section>
    <section class="mb-3">{!! '<p class="paragraph-5">Looking to replace your windows and doors? Reach out to Deluxe Windows for a complimentary estimate.</p>' !!}</section>
    <section class="mb-3">{!! '<h3 class="display-5 mid">Manufacture</h3>' !!}</section>
    <section class="mb-3">{!! '<p class="paragraph-6">If you are satisfied with the provided estimate and approve it, we will order windows and doors according to your specifications and needs.</p>' !!}</section>
    <section class="mb-3">{!! '<h3 class="display-5 mid">Remove and install</h3>' !!}</section>
    <section class="mb-3">{!! '<p class="paragraph-7">Once the products are ready, we will arrange a convenient time for installation and ensure your new windows and doors are expertly fitted.</p>' !!}</section>
    <section class="mb-3">{!! '<h3 class="display-5 mid">Final product</h3>' !!}</section>
    <section class="mb-3">{!! '<p class="paragraph-7">Upon completion, each window and door will be thoroughly inspected to ensure they operate correctly and meet the highest standards of fit and finish.</p>' !!}</section>
    <section class="mb-3">{!! '<h2 class="heading-42">Your dream home starts here.</h2>' !!}</section>
    <section class="mb-3">{!! '<p class="paragraph-20">Tell us about your project — we’ll take care of the rest.</p>' !!}</section>
    <section class="mb-3">{!! '<h1>Contact us</h1>' !!}</section>
    <section class="mb-3">{!! '<p class="paragraph-8">We’re here to help with all your door and window needs.</p>' !!}</section>
    <section class="mb-3">{!! '<div class="text-block-3">Phone number</div>' !!}</section>
    <section class="mb-3">{!! '<div>(650) 461-4446</div>' !!}</section>
    <section class="mb-3">{!! '<h2 class="display-4">Get Deluxe Windows for Less. 40%&nbsp;OFF* Windows</h2>' !!}</section>
    <section class="mb-3">{!! '<label for="email-banner" class="body-14"><em class="italic-text" data-w-id="1a5dd53b-cf65-d768-59e1-15f724b918ee">*Full Home Siding. Offer Expires 7/30/25</em></label>' !!}</section>
    <section class="mb-3">{!! '<label for="email-banner" class="body-14">Request a FREE No-Obligation Quote &amp; Expert Advice!</label>' !!}</section>
    <section class="mb-3">{!! '<label for="Name-2">Full name*</label>' !!}</section>
    <section class="mb-3">{!! '<div class="filled-icons-font"></div>' !!}</section>
    <section class="mb-3">{!! '<label for="Email-2">Email address*</label>' !!}</section>
    <section class="mb-3">{!! '<div class="filled-icons-font"></div>' !!}</section>
    <section class="mb-3">{!! '<label for="Phone-2">Phone number*</label>' !!}</section>
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

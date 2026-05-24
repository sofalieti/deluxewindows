@extends('webflow.layouts.app')

@section('title', 'About Us')

@section('content')
<div class="container py-4">
    <h1 class="mb-2">About Us</h1>
    <p class="text-secondary mb-4">Webflow slug: <code>about</code> | Path: <code>/about</code></p>

    <section class="mb-3">{!! '<h1 class="heading-14">Your Trusted Door <br data-w-id="9da18a18-b502-a73d-544d-352a0c547397">&amp; Window Experts</h1>' !!}</section>
    <section class="mb-3">{!! '<h2 class="heading-27">Over + 5000 Recently Completed Projects<br data-w-id="a023a4d5-1535-2774-172a-ce747519e896"></h2>' !!}</section>
    <section class="mb-3">{!! '<p class="paragraph-10">We offer a large variety of styles, sizes and colors to transform the design of your home.We\'ve partnered with the highest quality manufacturers in the industry and can ensure you get the best dealer prices and selections.We can ensure to provide you with guaranteed customer service, extensive product knowledge and a team of installation experts that will transform your view.</p>' !!}</section>
    <section class="mb-3">{!! '<h2 class="heading-6">Why Choose <br data-w-id="cec76795-85e0-c7db-9440-1ca0ce974096">Deluxe Window <br data-w-id="2383016c-80e6-9ce8-70d5-d316971f2a23">For Your Project?<br data-w-id="a349bc15-f31d-0347-0e02-68fcb95802e0"></h2>' !!}</section>
    <section class="mb-3">{!! '<h3 class="display-5 mid">Solving Your Problems</h3>' !!}</section>
    <section class="mb-3">{!! '<p class="paragraph-5">With our 20+ years of experience we have seen it all. From small to large projects, we will guide you through your window and door installation process.<br data-w-id="0d7a5a8c-c388-6238-85d5-39ca5541a206"></p>' !!}</section>
    <section class="mb-3">{!! '<h3 class="display-5 mid">Custom Design</h3>' !!}</section>
    <section class="mb-3">{!! '<p class="paragraph-6">Every project for Deluxe Windows is custom and we take great care to meet your project requirements.<br data-w-id="3b4545c8-55d2-8017-4e30-f9e3a3a9eadb"></p>' !!}</section>
    <section class="mb-3">{!! '<h3 class="display-5 mid">City and HOA Approved</h3>' !!}</section>
    <section class="mb-3">{!! '<p class="paragraph-7">We work closely with your HOA and city government to help you choose right windows and doors required for your home.<br data-w-id="dfe0a8aa-9d1d-0976-62e4-35aacee13561"></p>' !!}</section>
    <section class="mb-3">{!! '<h1 class="display-9 mid">Look at <br data-w-id="2aa14dbb-2c91-c99d-d0a1-2c5b1b69e65e">What People Say <span class="text-no-wrap" data-w-id="185df8ac-600c-3315-f7aa-ac1ee9cf1de5">about Us</span><br data-w-id="185df8ac-600c-3315-f7aa-ac1ee9cf1de7"></h1>' !!}</section>
    <section class="mb-3">{!! '<h1>Contact Us</h1>' !!}</section>
    <section class="mb-3">{!! '<p class="paragraph-8">We’re here to help with all your door and window needs.</p>' !!}</section>
    <section class="mb-3">{!! '<div class="text-block-3">Phone number</div>' !!}</section>
    <section class="mb-3">{!! '<div>(650) 461-4446</div>' !!}</section>
    <section class="mb-3">{!! '<h2 class="display-4">Get Deluxe Windows for Less. 40%&nbsp;OFF* Windows</h2>' !!}</section>
    <section class="mb-3">{!! '<label for="email-banner" class="body-14"><em class="italic-text" data-w-id="afbbd4be-3fae-4d3f-e616-87bc286fd7a4">*Full Home Siding. Offer Expires 7/30/25</em></label>' !!}</section>
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
    <section class="mb-3">{!! '<h2 class="heading-45">Your Dream Home Starts Here.</h2>' !!}</section>
    <section class="mb-3">{!! '<p class="paragraph-20">Tell us about your project — we’ll take care of the rest.</p>' !!}</section>

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

@extends('webflow.layouts.app')

@section('title', 'Home')

@section('content')
<div class="container py-4">
    <h1 class="mb-2">Home</h1>
    <p class="text-secondary mb-4">Webflow slug: <code></code> | Path: <code>/</code></p>

    <section class="mb-3">{!! '<h1 class="heading-4">Looking to Replace Your Windows in the Bay Area?</h1>' !!}</section>
    <section class="mb-3">{!! '<p class="paragraph-29">Get Deluxe Windows <br data-w-id="c3765d23-1eba-01a8-993c-c59200a6f71d">for Less. 40%&nbsp;OFF* Windows.<br data-w-id="c3765d23-1eba-01a8-993c-c59200a6f71f"></p>' !!}</section>
    <section class="mb-3">{!! '<h2 class="display-4">Get Deluxe Windows for Less. <br data-w-id="c3765d23-1eba-01a8-993c-c59200a6f726">40%&nbsp;OFF* Windows</h2>' !!}</section>
    <section class="mb-3">{!! '<label for="email-banner" class="body-14"><em class="italic-text" data-w-id="c3765d23-1eba-01a8-993c-c59200a6f729">Request a FREE No-Obligation Quote &amp; Expert Advice!</em></label>' !!}</section>
    <section class="mb-3">{!! '<label for="Name-2" class="field-label">Full name*</label>' !!}</section>
    <section class="mb-3">{!! '<div class="filled-icons-font"></div>' !!}</section>
    <section class="mb-3">{!! '<label for="Email-2" class="field-label-2">Email*</label>' !!}</section>
    <section class="mb-3">{!! '<div class="filled-icons-font"></div>' !!}</section>
    <section class="mb-3">{!! '<label for="Phone-2" class="field-label-3">Phone*</label>' !!}</section>
    <section class="mb-3">{!! '<div class="filled-icons-font"></div>' !!}</section>
    <section class="mb-3">{!! '<label for="Company" class="field-label-4">City</label>' !!}</section>
    <section class="mb-3">{!! '<label for="Message-2" class="field-label-5">Description</label>' !!}</section>
    <section class="mb-3">{!! '<label for="email-banner" class="body-14"><em class="italic-text" data-w-id="c3765d23-1eba-01a8-993c-c59200a6f755">*Windows Replacement. Offer Expires </em><span class="date-span italic-span" data-w-id="c3765d23-1eba-01a8-993c-c59200a6f757"><em class="italic-text" data-w-id="c3765d23-1eba-01a8-993c-c59200a6f758">03/10/26</em></span></label>' !!}</section>
    <section class="mb-3">{!! '<div>Thank you! Your submission has been received!</div>' !!}</section>
    <section class="mb-3">{!! '<div>Oops! Something went wrong while submitting the form.</div>' !!}</section>
    <section class="mb-3">{!! '<div>No items found.</div>' !!}</section>
    <section class="mb-3">{!! '<h2 class="heading-46">Discover Different <br data-w-id="87779e2a-0c09-17ad-e055-dbd837711a2e">Window Options</h2>' !!}</section>
    <section class="mb-3">{!! '<div>No items found.</div>' !!}</section>
    <section class="mb-3">{!! '<h2 class="display-8 mid">High-Quality Doors for Every Home</h2>' !!}</section>
    <section class="mb-3">{!! '<div>No items found.</div>' !!}</section>
    <section class="mb-3">{!! '<h2 class="heading-23">Our Previous Jobs</h2>' !!}</section>
    <section class="mb-3">{!! '<h2 class="heading-2">Your Trusted Partner in Professional Window Solutions</h2>' !!}</section>
    <section class="mb-3">{!! '<p class="paragraph-17">Serving Contractors, Property Managers, and Architects with Turnkey Services, Quality Products, and Peace of Mind from Start to Finish</p>' !!}</section>
    <section class="mb-3">{!! '<h3 class="display-5 mid">For Architects</h3>' !!}</section>
    <section class="mb-3">{!! '<p class="paragraph-5">As a trusted turnkey provider, we manage every stage in-house—delivering consistent, efficient results across multiple large-scale projects.</p>' !!}</section>
    <section class="mb-3">{!! '<h3 class="display-5 mid">For Contractors</h3>' !!}</section>
    <section class="mb-3">{!! '<p class="paragraph-6">We deliver turnkey window and glazing solutions with proven expertise in replacements for remodeling projects—backed by long-term guarantees and the capacity to efficiently manage multiple jobs at once.</p>' !!}</section>
    <section class="mb-3">{!! '<h3 class="display-5 mid">For Property<br data-w-id="3b9837ef-db59-4b5a-5d8b-40163640256f">managers/owners</h3>' !!}</section>
    <section class="mb-3">{!! '<p class="paragraph-7">A stress-free, all-in-one service that fits your schedule and budget—efficiently managing multiple jobs to simplify your toughest tasks.</p>' !!}</section>
    <section class="mb-3">{!! '<h2 class="heading-24">Your Dream Home <br data-w-id="d98f80e0-579a-af6b-061b-f10892a949ec">Starts Here</h2>' !!}</section>
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

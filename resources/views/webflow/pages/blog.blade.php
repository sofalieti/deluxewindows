@extends('webflow.layouts.app')

@section('title', 'Blogs Template')

@section('content')
<div class="container py-4">
    <h1 class="mb-2">Blogs Template</h1>
    <p class="text-secondary mb-4">Webflow slug: <code>detail_blog</code> | Path: <code>/blog</code></p>

    <section class="mb-3">{!! '<h2 class="display-8 mid">Read More Articles</h2>' !!}</section>
    <section class="mb-3">{!! '<div>Read more</div>' !!}</section>
    <section class="mb-3">{!! '<div>No items found.</div>' !!}</section>
    <section class="mb-3">{!! '<h2 class="heading-43">Your Dream Home Starts Here.</h2>' !!}</section>
    <section class="mb-3">{!! '<p class="paragraph-20">Tell us about your project — we’ll take care of the rest.</p>' !!}</section>
    <section class="mb-3">{!! '<h1 class="heading-34">Contact Us</h1>' !!}</section>
    <section class="mb-3">{!! '<p class="paragraph-8">We’re here to help with all your door and window needs.</p>' !!}</section>
    <section class="mb-3">{!! '<div class="text-block-3">Phone number</div>' !!}</section>
    <section class="mb-3">{!! '<div>855-355-0515</div>' !!}</section>
    <section class="mb-3">{!! '<h2 class="display-4">Get Deluxe Windows for Less. 40%&nbsp;OFF* Windows</h2>' !!}</section>
    <section class="mb-3">{!! '<label for="email-banner" class="body-14"><em class="italic-text" data-w-id="9eec908a-9245-54c9-222b-a3ee27647016">*Full Home Siding. Offer Expires 7/30/25</em></label>' !!}</section>
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

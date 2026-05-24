@extends('webflow.layouts.app')

@section('title', 'County Hubs Template')

@section('content')
<div class="container py-4">
    <h1 class="mb-2">County Hubs Template</h1>
    <p class="text-secondary mb-4">Webflow slug: <code>detail_county-hub-pages</code> | Path: <code>/county-hub-pages</code></p>

    <section class="mb-3">{!! '<p class="paragraph-62">Professional window &amp; door installation by Bay Area\'s most trusted team. Vinyl, fiberglass, wood &amp; aluminum — every brand, every style, free estimate.</p>' !!}</section>
    <section class="mb-3">{!! '<div class="text-block-46">✔</div>' !!}</section>
    <section class="mb-3">{!! '<div class="text-block-47">30+ Years Experience</div>' !!}</section>
    <section class="mb-3">{!! '<div class="text-block-46">✔</div>' !!}</section>
    <section class="mb-3">{!! '<div class="text-block-47">Employee Owned<br data-w-id="dcd175c2-d86f-a2d9-1064-979a34e6bdb3"></div>' !!}</section>
    <section class="mb-3">{!! '<div class="text-block-46">✔</div>' !!}</section>
    <section class="mb-3">{!! '<div class="text-block-47">Title 24 Compliant</div>' !!}</section>
    <section class="mb-3">{!! '<div class="text-block-46">✔</div>' !!}</section>
    <section class="mb-3">{!! '<div class="text-block-47">Licensed &amp; Insure</div>' !!}</section>
    <section class="mb-3">{!! '<h2 class="display-4">Get Deluxe Windows for Less. <br data-w-id="dcd175c2-d86f-a2d9-1064-979a34e6bdc4">40%&nbsp;OFF* Windows</h2>' !!}</section>
    <section class="mb-3">{!! '<label for="email-banner" class="body-14"><em class="italic-text" data-w-id="dcd175c2-d86f-a2d9-1064-979a34e6bdc7">Request a FREE No-Obligation Quote &amp; Expert Advice!</em></label>' !!}</section>
    <section class="mb-3">{!! '<label for="Name-2" class="field-label">Full name*</label>' !!}</section>
    <section class="mb-3">{!! '<div class="filled-icons-font"></div>' !!}</section>
    <section class="mb-3">{!! '<label for="Email-2" class="field-label-2">Email*</label>' !!}</section>
    <section class="mb-3">{!! '<div class="filled-icons-font"></div>' !!}</section>
    <section class="mb-3">{!! '<label for="Phone-2" class="field-label-3">Phone*</label>' !!}</section>
    <section class="mb-3">{!! '<div class="filled-icons-font"></div>' !!}</section>
    <section class="mb-3">{!! '<label for="Company" class="field-label-4">City</label>' !!}</section>
    <section class="mb-3">{!! '<label for="Message-2" class="field-label-5">Description</label>' !!}</section>
    <section class="mb-3">{!! '<label for="email-banner" class="body-14"><em class="italic-text" data-w-id="dcd175c2-d86f-a2d9-1064-979a34e6bdf3">*Windows Replacement. Offer Expires </em><span class="date-span italic-span" data-w-id="dcd175c2-d86f-a2d9-1064-979a34e6bdf5"><em class="italic-text" data-w-id="dcd175c2-d86f-a2d9-1064-979a34e6bdf6">07/10/26</em></span></label>' !!}</section>
    <section class="mb-3">{!! '<div>Thank you! Your submission has been received!</div>' !!}</section>
    <section class="mb-3">{!! '<div>Oops! Something went wrong while submitting the form.</div>' !!}</section>
    <section class="mb-3">{!! '<div>No items found.</div>' !!}</section>

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

@extends('webflow.layouts.app')

@section('title', 'Service Areas Template')

@section('content')
<div class="container py-4">
    <h1 class="mb-2">Service Areas Template</h1>
    <p class="text-secondary mb-4">Webflow slug: <code>detail_window-replacement</code> | Path: <code>/window-replacement</code></p>

    <section class="mb-3">{!! '<p class="paragraph-62">Professional window &amp; door installation by Bay Area\'s most trusted team. Vinyl, fiberglass, wood &amp; aluminum — every brand, every style, free estimate.</p>' !!}</section>
    <section class="mb-3">{!! '<div class="text-block-46">✔</div>' !!}</section>
    <section class="mb-3">{!! '<div class="text-block-47">30+ Years Experience</div>' !!}</section>
    <section class="mb-3">{!! '<div class="text-block-46">✔</div>' !!}</section>
    <section class="mb-3">{!! '<div class="text-block-47">Employee Owned<br data-w-id="21b3c05a-ef40-b860-186a-4a54c92e8c1f"></div>' !!}</section>
    <section class="mb-3">{!! '<div class="text-block-46">✔</div>' !!}</section>
    <section class="mb-3">{!! '<div class="text-block-47">Title 24 Compliant</div>' !!}</section>
    <section class="mb-3">{!! '<div class="text-block-46">✔</div>' !!}</section>
    <section class="mb-3">{!! '<div class="text-block-47">Licensed &amp; Insure</div>' !!}</section>
    <section class="mb-3">{!! '<h2 class="display-4">Get Deluxe Windows for Less. <br data-w-id="b3fcb7c0-398e-469d-7e45-aad63030bc14">40%&nbsp;OFF* Windows</h2>' !!}</section>
    <section class="mb-3">{!! '<label for="email-banner" class="body-14"><em class="italic-text" data-w-id="b3fcb7c0-398e-469d-7e45-aad63030bc17">Request a FREE No-Obligation Quote &amp; Expert Advice!</em></label>' !!}</section>
    <section class="mb-3">{!! '<label for="Name-2" class="field-label">Full name*</label>' !!}</section>
    <section class="mb-3">{!! '<div class="filled-icons-font"></div>' !!}</section>
    <section class="mb-3">{!! '<label for="Email-2" class="field-label-2">Email*</label>' !!}</section>
    <section class="mb-3">{!! '<div class="filled-icons-font"></div>' !!}</section>
    <section class="mb-3">{!! '<label for="Phone-2" class="field-label-3">Phone*</label>' !!}</section>
    <section class="mb-3">{!! '<div class="filled-icons-font"></div>' !!}</section>
    <section class="mb-3">{!! '<label for="Company" class="field-label-4">City</label>' !!}</section>
    <section class="mb-3">{!! '<label for="Message-2" class="field-label-5">Description</label>' !!}</section>
    <section class="mb-3">{!! '<label for="email-banner" class="body-14"><em class="italic-text" data-w-id="b3fcb7c0-398e-469d-7e45-aad63030bc43">*Windows Replacement. Offer Expires </em><span class="date-span italic-span" data-w-id="b3fcb7c0-398e-469d-7e45-aad63030bc45"><em class="italic-text" data-w-id="b3fcb7c0-398e-469d-7e45-aad63030bc46">06/10/26</em></span></label>' !!}</section>
    <section class="mb-3">{!! '<div>Thank you! Your submission has been received!</div>' !!}</section>
    <section class="mb-3">{!! '<div>Oops! Something went wrong while submitting the form.</div>' !!}</section>
    <section class="mb-3">{!! '<div class="text-block-46">✔</div>' !!}</section>
    <section class="mb-3">{!! '<div class="text-block-47">AAMA Certified Installers<br data-w-id="8955ed8f-227e-5d29-bfa3-9d5a77ca9572"></div>' !!}</section>
    <section class="mb-3">{!! '<div class="text-block-46">✔</div>' !!}</section>
    <section class="mb-3">{!! '<div class="text-block-47">Financing Available<br data-w-id="8955ed8f-227e-5d29-bfa3-9d5a77ca9578"></div>' !!}</section>
    <section class="mb-3">{!! '<div class="text-block-46">✔</div>' !!}</section>
    <section class="mb-3">{!! '<div class="text-block-47">40% Off — Limited Time</div>' !!}</section>
    <section class="mb-3">{!! '<a href="/" class="breadcrumb-link">Home</a>' !!}</section>
    <section class="mb-3">{!! '<div class="breadcrumb-div">/</div>' !!}</section>
    <section class="mb-3">{!! '<a href="#" class="breadcrumb-link hidden-link">SERVICE&nbsp;AREAS</a>' !!}</section>
    <section class="mb-3">{!! '<div class="breadcrumb-div hidden-txt">/</div>' !!}</section>
    <section class="mb-3">{!! '<a href="#" class="breadcrumb-link hidden-link"></a>' !!}</section>
    <section class="mb-3">{!! '<div>No items found.</div>' !!}</section>
    <section class="mb-3">{!! '<div>Request a Free Estimate</div>' !!}</section>
    <section class="mb-3">{!! '<h2 class="display-10 mid text-light">Windows Types We Install in <br data-w-id="15e2712f-6a76-a804-00c6-7443d6dd4428"></h2>' !!}</section>
    <section class="mb-3">{!! '<div>No items found.</div>' !!}</section>
    <section class="mb-3">{!! '<h2 class="display-10 mid text-light">Window Brands We Install in<br data-w-id="018ad382-cb73-917a-a79e-bedf91b4b0ba"></h2>' !!}</section>
    <section class="mb-3">{!! '<div>No items found.</div>' !!}</section>
    <section class="mb-3">{!! '<div class="f-heading-detail-small-3"><strong data-w-id="a16fa3fc-3523-d96c-a534-8625359b7dc0">Why Deluxe Windows</strong></div>' !!}</section>
    <section class="mb-3">{!! '<h2>Window Replacement FAQs </h2>' !!}</section>

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

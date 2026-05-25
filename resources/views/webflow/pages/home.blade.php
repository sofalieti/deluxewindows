@extends('webflow.layouts.app')

@section('title', 'Home')

@section('content')
<style>
    .home-redesign {
        --dw-bg: #f4f7fb;
        --dw-surface: #ffffff;
        --dw-text: #122238;
        --dw-muted: #4d5d73;
        --dw-primary: #0e5ea8;
        --dw-primary-soft: #e7f1fb;
        --dw-border: #d8e3f0;
        --dw-shadow: 0 10px 28px rgba(14, 39, 66, 0.08);
        --dw-shadow-hover: 0 14px 30px rgba(14, 39, 66, 0.12);
        max-width: 1160px;
        padding: 28px 16px 40px;
        border-radius: 20px;
        background:
            radial-gradient(circle at 0% 0%, rgba(14, 94, 168, 0.08), transparent 42%),
            radial-gradient(circle at 100% 100%, rgba(7, 125, 92, 0.08), transparent 38%),
            var(--dw-bg);
    }

    .home-redesign h1,
    .home-redesign h2,
    .home-redesign h3 {
        color: var(--dw-text);
        letter-spacing: -0.02em;
        line-height: 1.2;
        margin: 0;
    }

    .home-redesign p,
    .home-redesign label,
    .home-redesign div {
        color: var(--dw-muted);
        line-height: 1.55;
        margin: 0;
    }

    .home-redesign > h1 {
        font-size: clamp(1.7rem, 2.2vw, 2.4rem);
        margin-bottom: 8px;
    }

    .home-redesign > p.text-secondary {
        color: #3c4f67 !important;
        font-size: 0.95rem;
        margin-bottom: 20px !important;
    }

    .home-redesign > section.mb-3 {
        position: relative;
        border: 1px solid var(--dw-border);
        background: var(--dw-surface);
        border-radius: 14px;
        padding: 14px 16px;
        box-shadow: var(--dw-shadow);
        transition: transform 180ms ease, box-shadow 180ms ease, border-color 180ms ease;
    }

    .home-redesign > section.mb-3:hover {
        transform: translateY(-1px);
        box-shadow: var(--dw-shadow-hover);
        border-color: #c6d8ed;
    }

    .home-redesign > section:nth-of-type(1),
    .home-redesign > section:nth-of-type(2),
    .home-redesign > section:nth-of-type(3),
    .home-redesign > section:nth-of-type(4) {
        background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%);
        border-color: #c4daf1;
    }

    .home-redesign > section:nth-of-type(1) {
        border-left: 6px solid var(--dw-primary);
    }

    .home-redesign .display-4 {
        color: #0a3f73;
        font-size: clamp(1.5rem, 3vw, 2.2rem);
        font-weight: 700;
    }

    .home-redesign .display-5,
    .home-redesign .display-8,
    .home-redesign .heading-23,
    .home-redesign .heading-24,
    .home-redesign .heading-46,
    .home-redesign .heading-2 {
        font-size: clamp(1.25rem, 2.4vw, 1.85rem);
        font-weight: 700;
        color: #153257;
    }

    .home-redesign .paragraph-29,
    .home-redesign .paragraph-17,
    .home-redesign .paragraph-20,
    .home-redesign .paragraph-5,
    .home-redesign .paragraph-6,
    .home-redesign .paragraph-7 {
        font-size: 1rem;
        color: var(--dw-muted);
        max-width: 72ch;
    }

    .home-redesign .field-label,
    .home-redesign .field-label-2,
    .home-redesign .field-label-3,
    .home-redesign .field-label-4,
    .home-redesign .field-label-5 {
        font-weight: 600;
        color: #1a3559;
    }

    .home-redesign .filled-icons-font {
        display: inline-flex;
        min-width: 44px;
        min-height: 44px;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        border: 1px solid #c7dbef;
        background: var(--dw-primary-soft);
        color: var(--dw-primary);
        font-size: 1.15rem;
    }

    .home-redesign .italic-text {
        color: #29496d;
    }

    .home-redesign .date-span {
        font-weight: 700;
        color: #0f4e90;
    }

    .home-redesign .card {
        border: 1px solid var(--dw-border);
        border-radius: 14px;
        box-shadow: var(--dw-shadow);
    }

    .home-redesign .card-body pre {
        max-height: 260px;
        overflow: auto;
        border-radius: 10px;
        background: #f3f8fd;
        border: 1px solid #d6e6f7;
        padding: 10px;
    }

    @media (max-width: 767px) {
        .home-redesign {
            border-radius: 14px;
            padding: 20px 12px 28px;
        }

        .home-redesign > section.mb-3 {
            padding: 12px;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .home-redesign > section.mb-3 {
            transition: none;
        }

        .home-redesign > section.mb-3:hover {
            transform: none;
        }
    }
</style>
<div class="container py-4 home-redesign">
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

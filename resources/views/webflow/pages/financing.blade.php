@extends('webflow.layouts.app')

@section('title', 'Financing Options')

@section('content')
<div class="container py-4">
    <h1 class="mb-2">Financing Options</h1>
    <p class="text-secondary mb-4">Webflow slug: <code>financing</code> | Path: <code>/financing</code></p>

    <section class="mb-3">{!! '<h1 class="display-9 mid">Upgrade to Energy Efficient Windows and Doors for Less</h1>' !!}</section>
    <section class="mb-3">{!! '<h2 class="display-5 mid">No FICO</h2>' !!}</section>
    <section class="mb-3">{!! '<p>Your credit rating does not impact your ability to qualify.</p>' !!}</section>
    <section class="mb-3">{!! '<div class="check-icon feature-plan"></div>' !!}</section>
    <section class="mb-3">{!! '<div class="display-2">No Credit Score Required</div>' !!}</section>
    <section class="mb-3">{!! '<div class="check-icon feature-plan"></div>' !!}</section>
    <section class="mb-3">{!! '<div class="display-2">Fast &amp; Simple Approval<br data-w-id="8cec98af-57af-e175-04db-53785e1fa455"></div>' !!}</section>
    <section class="mb-3">{!! '<div class="check-icon feature-plan"></div>' !!}</section>
    <section class="mb-3">{!! '<div class="display-2">Improve Home Value Now<br data-w-id="86fe2bda-4cfd-053a-8f06-94ded97fc380"></div>' !!}</section>
    <section class="mb-3">{!! '<div class="check-icon feature-plan"></div>' !!}</section>
    <section class="mb-3">{!! '<div class="display-2">Second Chance Financing<br data-w-id="a3be45eb-4ebf-f3a1-6e22-2a8f953bd135"></div>' !!}</section>
    <section class="mb-3">{!! '<h2 class="display-5 mid">Lower Fixed Rates</h2>' !!}</section>
    <section class="mb-3">{!! '<p>Payment remains the same for the life of your financing.</p>' !!}</section>
    <section class="mb-3">{!! '<div class="check-icon feature-plan"></div>' !!}</section>
    <section class="mb-3">{!! '<div class="display-2">Budget-Friendly Payments</div>' !!}</section>
    <section class="mb-3">{!! '<div class="check-icon feature-plan"></div>' !!}</section>
    <section class="mb-3">{!! '<div class="display-2">Long-Term Savings</div>' !!}</section>
    <section class="mb-3">{!! '<div class="check-icon feature-plan"></div>' !!}</section>
    <section class="mb-3">{!! '<div class="display-2">Secure &amp; Stable</div>' !!}</section>
    <section class="mb-3">{!! '<div class="check-icon feature-plan"></div>' !!}</section>
    <section class="mb-3">{!! '<div class="display-2">Immediate Upgrades</div>' !!}</section>
    <section class="mb-3">{!! '<h2 class="display-5 mid">Longer Terms</h2>' !!}</section>
    <section class="mb-3">{!! '<p>Flexible repayment terms - <br data-w-id="d07cd3c4-7fc4-9087-2a47-9caf818c53aa">up to 30 years for some projects.</p>' !!}</section>
    <section class="mb-3">{!! '<div class="check-icon feature-plan"></div>' !!}</section>
    <section class="mb-3">{!! '<div class="display-2">Custom Repayment Plans<br data-w-id="0c3410b9-a9ad-43fd-c111-0135c1f9d34d"></div>' !!}</section>
    <section class="mb-3">{!! '<div class="check-icon feature-plan"></div>' !!}</section>
    <section class="mb-3">{!! '<div class="display-2">More Buying Power<br data-w-id="e44cac61-cc23-425e-1ac6-eb8b063743cc"></div>' !!}</section>
    <section class="mb-3">{!! '<div class="check-icon feature-plan"></div>' !!}</section>
    <section class="mb-3">{!! '<div class="display-2">Up to 30-Year Terms<br data-w-id="3eff527c-5e82-b42e-03dc-416521364036"></div>' !!}</section>
    <section class="mb-3">{!! '<div class="check-icon feature-plan"></div>' !!}</section>
    <section class="mb-3">{!! '<div class="display-2">Upgrade Without Stress</div>' !!}</section>
    <section class="mb-3">{!! '<h3 class="faqs-title">Apply</h3>' !!}</section>
    <section class="mb-3">{!! '<p class="accordion-paragraph">Select Deluxe Windows who will help you through the process every step of the way.</p>' !!}</section>
    <section class="mb-3">{!! '<h3 class="faqs-title">Sign</h3>' !!}</section>
    <section class="mb-3">{!! '<p class="accordion-paragraph">Sign financing documents electronically.No need to go to the bank.</p>' !!}</section>
    <section class="mb-3">{!! '<h3 class="faqs-title">Install</h3>' !!}</section>
    <section class="mb-3">{!! '<p class="accordion-paragraph">Deluxe Windows will install your energy efficient windows and doors.<br data-w-id="d5bd7c9d-8183-fc6b-66b7-37acdd19938d"></p>' !!}</section>
    <section class="mb-3">{!! '<h2 class="heading-29">Your Dream Home Starts Here.</h2>' !!}</section>
    <section class="mb-3">{!! '<p class="paragraph-20">Tell us about your project — we’ll take care of the rest.</p>' !!}</section>
    <section class="mb-3">{!! '<h1 class="heading-30">Contact Us</h1>' !!}</section>
    <section class="mb-3">{!! '<p class="paragraph-8">We’re here to help with all your door and window needs.</p>' !!}</section>
    <section class="mb-3">{!! '<div class="text-block-3">Phone number</div>' !!}</section>
    <section class="mb-3">{!! '<div>(650) 461-4446</div>' !!}</section>

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

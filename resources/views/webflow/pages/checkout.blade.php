@extends('webflow.layouts.app')

@section('title', '🛒 Checkout')

@section('content')
<div class="container py-4">
    <h1 class="mb-2">🛒 Checkout</h1>
    <p class="text-secondary mb-4">Webflow slug: <code>checkout</code> | Path: <code>/checkout</code></p>

    <section class="mb-3">{!! '<h1>Checkout</h1>' !!}</section>
    <section class="mb-3">{!! '<p>Please review your checkout details below. If everything is correct, place your order and you will receive more information <span class="text-no-wrap" data-w-id="83b4431d-be2d-a693-452d-4d0163c93193">via email.</span></p>' !!}</section>
    <section class="mb-3">{!! '<h2 class="display-5">Customer Info</h2>' !!}</section>
    <section class="mb-3">{!! '<div>* Required</div>' !!}</section>
    <section class="mb-3">{!! '<div for="wf-ecom-email">Email *</div>' !!}</section>
    <section class="mb-3">{!! '<h2 class="display-5">Shipping Address</h2>' !!}</section>
    <section class="mb-3">{!! '<div>* Required</div>' !!}</section>
    <section class="mb-3">{!! '<div for="wf-ecom-shipping-name">Full Name *</div>' !!}</section>
    <section class="mb-3">{!! '<div for="wf-ecom-shipping-address">Street Address *</div>' !!}</section>
    <section class="mb-3">{!! '<div for="wf-ecom-shipping-city">City *</div>' !!}</section>
    <section class="mb-3">{!! '<div for="wf-ecom-shipping-state">State/Province</div>' !!}</section>
    <section class="mb-3">{!! '<div for="wf-ecom-shipping-zip">Zip/Postal Code *</div>' !!}</section>
    <section class="mb-3">{!! '<div for="wf-ecom-shipping-country">Country *</div>' !!}</section>
    <section class="mb-3">{!! '<h2 class="display-5">Shipping Method</h2>' !!}</section>
    <section class="mb-3">{!! '<div class="display-3 mid"></div>' !!}</section>
    <section class="mb-3">{!! '<div class="paragraph-small text-paragraph"></div>' !!}</section>
    <section class="mb-3">{!! '<div class="text-titles"></div>' !!}</section>
    <section class="mb-3">{!! '<div>No shipping methods are available for the address given.</div>' !!}</section>
    <section class="mb-3">{!! '<h2 class="display-5">Payment Info</h2>' !!}</section>
    <section class="mb-3">{!! '<div>* Required</div>' !!}</section>
    <section class="mb-3">{!! '<div for="">Card Number *</div>' !!}</section>
    <section class="mb-3">{!! '<div for="">Expiration Date *</div>' !!}</section>
    <section class="mb-3">{!! '<div for="">Security Code *</div>' !!}</section>
    <section class="mb-3">{!! '<div for="billing-address-toggle" class="mg-bottom-0">Billing address same as shipping</div>' !!}</section>
    <section class="mb-3">{!! '<h2 class="display-5">Billing Address</h2>' !!}</section>
    <section class="mb-3">{!! '<div>* Required</div>' !!}</section>
    <section class="mb-3">{!! '<div for="wf-ecom-billing-name">Full Name *</div>' !!}</section>
    <section class="mb-3">{!! '<div for="wf-ecom-billing-address">Street Address *</div>' !!}</section>
    <section class="mb-3">{!! '<div for="wf-ecom-billing-city">City *</div>' !!}</section>
    <section class="mb-3">{!! '<div for="wf-ecom-billing-state">State/Province</div>' !!}</section>
    <section class="mb-3">{!! '<div for="wf-ecom-billing-zip">Zip/Postal Code *</div>' !!}</section>
    <section class="mb-3">{!! '<div for="wf-ecom-billing-country">Country *</div>' !!}</section>
    <section class="mb-3">{!! '<h2 class="display-5">Items in Order</h2>' !!}</section>
    <section class="mb-3">{!! '<a class="order-item-title" href="#"></a>' !!}</section>
    <section class="mb-3">{!! '<div>Quantity: </div>' !!}</section>
    <section class="mb-3">{!! '<div class="text-titles"></div>' !!}</section>
    <section class="mb-3">{!! '<div class="capitalize-every-word"></div>' !!}</section>
    <section class="mb-3">{!! '<div></div>' !!}</section>
    <section class="mb-3">{!! '<div class="text-titles"></div>' !!}</section>
    <section class="mb-3">{!! '<h2 class="display-5">Order Summary</h2>' !!}</section>
    <section class="mb-3">{!! '<div>Subtotal</div>' !!}</section>
    <section class="mb-3">{!! '<div class="text-titles"></div>' !!}</section>
    <section class="mb-3">{!! '<div></div>' !!}</section>
    <section class="mb-3">{!! '<div class="text-titles"></div>' !!}</section>
    <section class="mb-3">{!! '<div>Total</div>' !!}</section>
    <section class="mb-3">{!! '<div class="text-titles mid"></div>' !!}</section>
    <section class="mb-3">{!! '<div href="#" value="Place Order" class="primary-button mg-bottom-extra-small center---mbp"></div>' !!}</section>
    <section class="mb-3">{!! '<div role="button" aria-haspopup="dialog" aria-label="Apple Pay" data-node-type="commerce-cart-apple-pay-button" class="pay-button mg-bottom-0"><div data-w-id="89bc312a-17a7-75c8-aaea-081ca0ce29ae"></div></div>' !!}</section>
    <section class="mb-3">{!! '<div role="button" tabindex="0" aria-haspopup="dialog" data-node-type="commerce-cart-quick-checkout-button" class="pay-button mg-bottom-0"><div data-w-id="89bc312a-17a7-75c8-aaea-081ca0ce29b0"></div><div data-w-id="89bc312a-17a7-75c8-aaea-081ca0ce29b1"></div><div data-w-id="89bc312a-17a7-75c8-aaea-081ca0ce29b2">Pay with browser.</div></div>' !!}</section>
    <section class="mb-3">{!! '<div>Pay with browser.</div>' !!}</section>

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

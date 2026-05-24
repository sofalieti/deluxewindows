@extends('webflow.layouts.app')

@section('title', '🛒 Checkout (PayPal)')

@section('content')
<div class="container py-4">
    <h1 class="mb-2">🛒 Checkout (PayPal)</h1>
    <p class="text-secondary mb-4">Webflow slug: <code>paypal-checkout</code> | Path: <code>/paypal-checkout</code></p>

    <section class="mb-3">{!! '<h1>Checkout</h1>' !!}</section>
    <section class="mb-3">{!! '<p>Please review your checkout details below. If everything is correct, place your order and you will receive more information <span class="text-no-wrap" data-w-id="65232684-160c-adf2-ae7d-0a66b278bab4">via email.</span></p>' !!}</section>
    <section class="mb-3">{!! '<h2 class="display-5">Shipping Method</h2>' !!}</section>
    <section class="mb-3">{!! '<div class="display-3 mid"></div>' !!}</section>
    <section class="mb-3">{!! '<div class="paragraph-small text-paragraph"></div>' !!}</section>
    <section class="mb-3">{!! '<div></div>' !!}</section>
    <section class="mb-3">{!! '<div>No shipping methods are available for the address given.</div>' !!}</section>
    <section class="mb-3">{!! '<h2 class="display-5">Customer Information</h2>' !!}</section>
    <section class="mb-3">{!! '<div>Email</div>' !!}</section>
    <section class="mb-3">{!! '<div></div>' !!}</section>
    <section class="mb-3">{!! '<div>Shipping Address</div>' !!}</section>
    <section class="mb-3">{!! '<div></div>' !!}</section>
    <section class="mb-3">{!! '<div></div>' !!}</section>
    <section class="mb-3">{!! '<div></div>' !!}</section>
    <section class="mb-3">{!! '<div></div>' !!}</section>
    <section class="mb-3">{!! '<div></div>' !!}</section>
    <section class="mb-3">{!! '<div></div>' !!}</section>
    <section class="mb-3">{!! '<div></div>' !!}</section>
    <section class="mb-3">{!! '<h2 class="display-5">Payment Info</h2>' !!}</section>
    <section class="mb-3">{!! '<div>Payment Info</div>' !!}</section>
    <section class="mb-3">{!! '<div></div>' !!}</section>
    <section class="mb-3">{!! '<div></div>' !!}</section>
    <section class="mb-3">{!! '<div></div>' !!}</section>
    <section class="mb-3">{!! '<div> / </div>' !!}</section>
    <section class="mb-3">{!! '<div></div>' !!}</section>
    <section class="mb-3">{!! '<div>Billing Address</div>' !!}</section>
    <section class="mb-3">{!! '<div></div>' !!}</section>
    <section class="mb-3">{!! '<div></div>' !!}</section>
    <section class="mb-3">{!! '<div></div>' !!}</section>
    <section class="mb-3">{!! '<div></div>' !!}</section>
    <section class="mb-3">{!! '<div></div>' !!}</section>
    <section class="mb-3">{!! '<div></div>' !!}</section>
    <section class="mb-3">{!! '<div></div>' !!}</section>
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
    <section class="mb-3">{!! '<div href="#" value="Place Order" class="primary-button mg-bottom-0"></div>' !!}</section>

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

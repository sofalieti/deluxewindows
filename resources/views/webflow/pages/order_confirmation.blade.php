@extends('webflow.layouts.app')

@section('title', '🛒 Order Confirmation')

@section('content')
<div class="container py-4">
    <h1 class="mb-2">🛒 Order Confirmation</h1>
    <p class="text-secondary mb-4">Webflow slug: <code>order-confirmation</code> | Path: <code>/order-confirmation</code></p>

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
    <section class="mb-3">{!! '<h2 class="display-5">Shipping Method</h2>' !!}</section>
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
